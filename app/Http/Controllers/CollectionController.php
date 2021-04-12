<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Question;
use App\Models\Collection;
use App\Models\Elaboration;
use App\Rules\HashIdExists;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;
use App\Http\Resources\CollectionResource;
use App\Support\CollectionQuestionsDocument;

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
            'questions.chapters.methodology'
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
