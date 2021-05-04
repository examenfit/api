<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Question;
use App\Models\Collection;
use App\Models\Elaboration;
use App\Rules\HashIdExists;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Vinkla\Hashids\Facades\Hashids;
use App\Http\Resources\CollectionResource;
use App\Support\CollectionQuestionsDocument;
use App\Support\DocumentMarkup;

class CollectionController extends Controller
{
    public function show(Collection $collection, Topic $topic)
    {
        $collection->load([
            'author',
            'questions' => function ($query) use ($topic) {
                if ($topic->id) {
                    $query->where('topic_id', $topic->id);
                }

                $query->orderBy('topic_id', 'ASC')
                    ->orderBy('number', 'ASC');
            },
            'questions.answers.sections.tips',
            'questions.tips',
            'questions.topic.attachments',
            'questions.attachments',
            'questions.tags',
            'questions.dependencies',
            'questions.chapters.methodology',
            'questions.chapters.parent',
        ]);

        return new CollectionResource($collection);
    }

    public function showCollectionQuestionsDocument(Request $request, Collection $collection)
    {
        $path = storage_path("app/public/collections/{$collection->hash_id}.docx");

        $document = new CollectionQuestionsDocument();
        $document->createDocument($collection);
        $document->saveDocument($path, 'docx');
        return response()->download($path, 'collection.docx');
    }

    public function showCollectionQuestionsPdf(Request $request, Collection $collection)
    {
        Log::info('showCollectionQuestionsPdf');

        $api = url("/api/download-collection-html");
        $api = str_replace("http://localhost:8000", "https://staging-api.examenfit.nl", $api);

        Log::info("api=$api");

        $server = config('app.examenfit_scripts_url');

        $hash = $collection->hash_id;
        $pdf = "${hash}.pdf";
        $tmp = "/tmp/${pdf}";

        Log::info("pdf=$pdf");
        Log::info("tmp=$tmp");

        // currently ssh authentication goes with public key authentication
        // in the future this may need to become ssh -i id_rsa or something alike
        $generate = "ssh examenfit@$server make/pdf $hash $api";
        $retrieve = "scp examenfit@$server:pdf/$pdf $tmp";

        Log::info($generate);
        shell_exec($generate);

        Log::info($retrieve);
        shell_exec($retrieve);

        Log::info("response");
        return response()->download($tmp);
    }

    public function showCollectionQuestionsHtml(Request $request, Collection $collection)
    {
        $markup = new DocumentMarkup();

        $collection->load([
            'author',
            'questions' => fn ($q) => $q->orderBy('topic_id', 'ASC')->orderBy('number', 'ASC'),
            'questions.attachments',
            'questions.topic',
            'questions.topic.attachments',
            'questions.topic.exam',
            'questions.topic.exam.course',
            'questions.dependencies',
        ]);

        $topic_id = -1;
        $points = 0;
        $time_in_minutes = 0;

        $topics = [];
        $use_text = [];
        $use_introduction = [];
        $use_attachments = [];
        $use_appendixes = [];

        foreach ($collection['questions'] as $question) {

            $points += $question['points'];
            $time_in_minutes += $question['time_in_minutes'];

            $id = $question['id'];
            $use_text[$id] = true;
            $use_introductions[$id] = true;
            $use_attachments[$id] = true;
            $use_appendixes[$id] = true;

            foreach ($question['dependencies'] as $dependency) {
                $pivot = $dependency['pivot'];
                $id = $pivot['question_id'];

                if ($pivot['introduction']) $use_introduction[$id] = true;
                if ($pivot['attachments']) $use_attachments[$id] = true;
                if ($pivot['appendixes']) $use_appendixes[$id] = true;
            }

            $topic = $question['topic'];
            if ($topic['id'] !== $topic_id) {
                $topics[] = $topic;
                $topic_id = $topic['id'];
            }
        }

        $appendixes = [];
        $appendix_added = [];

        foreach ($topics as $topic) {
            $topic['introduction'] = $markup->fix($topic['introduction']);

            foreach ($topic['questions'] as $question) {
                $id = $question['id'];

                $question['use_text'] = array_key_exists($id, $use_text);
                $question['use_introduction'] = array_key_exists($id, $use_introduction);
                $question['use_attachments'] = array_key_exists($id, $use_attachments);

                if (array_key_exists($id, $use_appendixes)) {
                    foreach ($question['appendixes'] as $appendix) {
                        $id = $appendix->id;
                        if (array_key_exists($id, $appendix_added)) {
                            /* skip */
                        } else {
                            $appendixes[] = $appendix;
                            $appendix_added[$id] = true;
                        }
                    }
                }

                $question['introduction'] = $markup->fix($question['introduction']);
                $question['text'] = $markup->fix($question['text']);

                $c = $collection->hash_id;
                $q = $question->hash_id;
                $t = $topic->hash_id;

                $question['url'] = "https://app.examenfit.nl/c/{$c}/{$t}/{$q}";
            }
        }

        $collection['topics'] = $topics;
        $collection['points'] = $points;
        $collection['time_in_minutes'] = $time_in_minutes;

        $collection['appendixes'] = $appendixes;

        return view('pdf', $collection);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'questions' => 'required|array',
            'questions.*' => new HashIdExists('questions'),
        ]);

        $collection = Collection::create([
            'name' => $data['name'],
        ]);

        $collection->questions()->sync(
            collect($data['questions'])->map(
                fn ($q) => Hashids::decode($q)[0],
            )
        );

        $collection->load('topics');

        return new CollectionResource($collection);
    }

    public function storeElaboration(Request $request, Collection $collection, Question $question)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png',
            'name' => 'nullable|string',
        ]);

        Elaboration::create([
            'collection_id' => $collection->id,
            'question_id' => $question->id,
            'name' => $data['name'],
            'file_path' => $data['file']->store("collections/{$collection->hash_id}/elaborations"),
        ]);

        return response()->isSuccessful();
    }
}
