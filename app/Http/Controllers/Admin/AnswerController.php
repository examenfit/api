<?php

namespace App\Http\Controllers\Admin;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnswerResource;
use App\Models\AnswerSection;

class AnswerController extends Controller
{
    public function show(Answer $answer)
    {
        $answer->load('sections.tips', 'question.tips');
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

        return $this->show($answer);
    }

    public function updateSection(Request $request, Answer $answer, AnswerSection $answerSection)
    {
        $data = $request->validate([
            'points' => 'required|integer',
            'correction' => 'nullable|string',
            'text' => 'nullable|string',
            'elaboration' => 'nullable|string',
            'explanation' => 'nullable|string',
        ]);

        $answerSection->update([
            'points' => $data['points'],
            'correction' => Arr::get($data, 'correction'),
            'text' => Arr::get($data, 'text'),
            'elaboration' => Arr::get($data, 'elaboration'),
            'explanation' => Arr::get($data, 'explanation'),
        ]);

        return response(null, 200);
    }
}
