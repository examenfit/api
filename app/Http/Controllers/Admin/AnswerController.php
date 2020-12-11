<?php

namespace App\Http\Controllers\Admin;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\Request;
use Vinkla\Hashids\Facades\Hashids;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnswerResource;

class AnswerController extends Controller
{
    public function show(Answer $answer)
    {
        $answer->load('sections');
        return new AnswerResource($answer);
    }

    public function store(Request $request, Question $question)
    {
        $data = $request->validate([
            'type' => 'required|in:correction,didactic',
        ]);

        $answer = $question->answers()->create($data);

        return new AnswerResource($answer);
    }

    public function update(Request $request, Answer $answer)
    {
        $data = $request->validate([
            'sections' => 'array|min:1',
            'sections.*.id' => 'nullable',
            'sections.*.text' => 'required|string',
            'secionts.*.points' => 'required|integer'
        ]);

        $answer->sections()->delete();

        $answer->sections()->createMany($data['sections']);

        $answer->load('sections');

        return new AnswerResource($answer);
    }
}
