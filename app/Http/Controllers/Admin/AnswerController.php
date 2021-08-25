<?php

namespace App\Http\Controllers\Admin;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    public function delete(Answer $answer)
    {
        $id = $answer->id;
        $answer->delete();
        return response()->json([ 'message' => 'deleted Answer#'.$id, 'status' => 'ok' ]);
    }

    public function addStep(Answer $answer)
    {
        $answer->sections()->create([
          'correction' => '',
          'text' => '',
          'elaboration' => '',
          'explanation' => '',
          'points' => 0,
        ]);
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
            'remark' => 'nullable|string',
            'sections' => 'array',
            'sections.*.id' => 'nullable',
            'sections.*.text' => 'required|string',
            'secionts.*.points' => 'required|integer'
        ]);

        Log::info('remark = '.$data['remark']);

        if (isset($data['sections']) && count($data['sections'])) {
            $answer->sections()->delete();
            $answer->sections()->createMany($data['sections']);
        }

        //if (isset($data['remark'])) {
            Log::info('updating');
            $answer->update(['remark' => $data['remark']]);
        //}

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

    public function deleteStep(Answer $answer, AnswerSection $answerSection)
    {
        $id = $answerSection->id;
        $answerSection->delete();
        $answer->load('sections.tips', 'question.tips');
        return new AnswerResource($answer);
    }

    public function fix()
    {
        Artisan::call('ef:splitMultipleMethods');
    }
}
