<?php

namespace App\Http\Controllers;

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
    public function show(Collection $collection)
    {
        $collection->load([
            'author',
            'questions' => fn ($q) => $q->orderBy('topic_id', 'ASC')->orderBy('number', 'ASC'),
            'questions.answers.sections.tips',
            'questions.tips',
            'questions.topic.attachments',
            'questions.attachments',
            'questions.tags',
            'questions.chapters.methodology'
        ]);
        // $collection = collect($collection->toArray());

        // $questions = collect($collection['questions']);
        // dump ($questions->groupBy('topic_id'));
        return new CollectionResource($collection);
    }

    public function showCollectionQuestionsDocument(Collection $collection)
    {
        $hash = $collection->hash_id;
        $file = '/tmp/{$hash}.docx'; // use temp filename
        $document = new CollectionQuestionsDocument();
        $document->createDocument($collection);
        $document->saveDocument($file);
        return response()->download($file, 'collection.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'questions' => 'required|array',
            'questions.*' => new HashIdExists('questions'),
        ]);

        $collection = Collection::create([
            'name' => 'Mijn opgaven (' . date('d-m-Y') . ')',
        ]);

        $collection->questions()->sync(
            collect($data['questions'])->map(
                fn ($q) => Hashids::decode($q)[0],
            )
        );

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
