<?php

namespace App\Http\Controllers\Admin;

use App\Models\Topic;
use App\Models\Question;
use App\Rules\HashIdExists;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;

class QuestionController extends Controller
{
    public function store(Request $request, Topic $topic)
    {
        $data = $request->validate([
            'number' => 'required|integer',
            'points' => 'required|integer',
            'proportion_value' => 'required|integer',
            'introduction' => 'required|string',
            'text' => 'required|string',
            'answerSteps' => 'array',
            'answerSteps.*.text' => 'required|string',
            'answerSteps.*.points' => 'required|integer',
            'facets' => 'array',
            'facets.*.id' => ['required', new HashIdExists('facets')],
            'attachments' => 'array',
            'attachments.*.id' => ['required', new HashIdExists('attachments')],
        ]);

        $question = $topic->questions()->create($data);

        if (isset($data['attachments'])) {
            $question->addAttachments($data['attachments']);
        }

        if (isset($data['answerSteps'])) {
            $answer = $question->answers()->create([
                'type' => 'correction',
            ]);

            $answer->sections()->createMany($data['answerSteps']);
        }

        if (isset($data['facets'])) {
            $question->addFacets($data['facets']);
        }

        $exam = $topic->exam;
        $exam->load('topics.questions', 'files');

        return new ExamResource($exam);
    }

    public function update(Request $request, Question $question)
    {
        $data = $request->validate([
            'number' => 'required|integer',
            'points' => 'required|integer',
            'introduction' => 'required|string',
            'text' => 'required|string',
            'answerSteps' => 'array',
            'answerSteps.*.text' => 'required|string',
            'answerSteps.*.points' => 'required|integer',
            'attachments' => 'array',
            'attachments.*.id' => ['required', new HashIdExists('attachments')],
        ]);

        if (isset($data['attachments'])) {
            $question->addAttachments($data['attachments']);
        }

        if (isset($data['answerSteps'])) {
            $answer = $question->answers()->create([
                'type' => 'correction',
            ]);

            $answer->sections()->createMany($data['answerSteps']);
        }

        $question->update($data);

        $exam = $question->topic->exam;
        $exam->load('topics.questions', 'files');

        return new ExamResource($exam);
    }
}
