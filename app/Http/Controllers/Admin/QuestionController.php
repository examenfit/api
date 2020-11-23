<?php

namespace App\Http\Controllers\Admin;

use App\Models\Topic;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use App\Rules\HashIdExists;

class QuestionController extends Controller
{
    public function store(Request $request, Topic $topic)
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

        $exam = $topic->exam;
        $exam->load('topics.questions');

        return new ExamResource($exam);
    }
}
