<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Elaboration;
use App\Models\Collection;
use Illuminate\Http\Request;
use App\Http\Resources\CollectionResource;

class CollectionController extends Controller
{
    public function show(Collection $collection)
    {
        $collection->load('questions.answers.sections');
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
