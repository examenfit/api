<?php

namespace App\Http\Controllers\Admin;

use App\Models\Topic;
use App\Models\Question;
use App\Rules\HashIdExists;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use App\Http\Resources\QuestionResource;

class QuestionController extends Controller
{
    public function show(Question $question)
    {
        $question->load('topic', 'attachments', 'answers.sections');

        return new QuestionResource($question);
    }

    public function store(Request $request, Topic $topic)
    {
        $data = $request->validate([
            'number' => 'required|integer',
            'points' => 'required|integer',
            'proportion_value' => 'nullable|numeric',
            'introduction' => 'required|string',
            'text' => 'required|string',
            'answerSections' => 'array',
            'answerSections.*.text' => 'required|string',
            'answerSections.*.points' => 'required|integer',
            'domain_id' => ['required', new HashIdExists('domains')],
            'type_id' => ['required', new HashIdExists('question_types')],
            'tags.*.id' => ['required', new HashIdExists('tags')],
            'attachments' => 'array',
            'attachments.*.id' => ['required', new HashIdExists('attachments')],
        ]);

        $question = $topic->questions()->create($data);

        if (isset($data['attachments'])) {
            $question->addAttachments($data['attachments']);
        }

        if (isset($data['answerSections'])) {
            $answer = $question->answers()->create([
                'type' => 'correction',
            ]);

            $answer->sections()->createMany($data['answerSections']);
        }

        if (isset($data['tags'])) {
            $question->addTags($data['tags']);
        }

        return app('App\Http\Controllers\Admin\ExamController')
            ->show($topic->exam);
    }

    public function update(Request $request, Question $question)
    {
        $data = $request->validate([
            'number' => 'required|integer',
            'points' => 'required|integer',
            'proportion_value' => 'nullable|numeric',
            'introduction' => 'required|string',
            'text' => 'required|string',
            'answerSections' => 'array',
            'answerSections.*.text' => 'required|string',
            'answerSections.*.points' => 'required|integer',
            'domain_id' => ['required', new HashIdExists('domains')],
            'type_id' => ['required', new HashIdExists('question_types')],
            'tags.*.id' => ['required', new HashIdExists('tags')],
            'attachments' => 'array',
            'attachments.*.id' => ['required', new HashIdExists('attachments')],
        ]);

        if (isset($data['attachments'])) {
            $question->addAttachments($data['attachments']);
        }

        if (isset($data['answerSections'])) {
            $question->answers()->delete();

            $answer = $question->answers()->create([
                'type' => 'correction',
            ]);

            $answer->sections()->createMany($data['answerSections']);
        }

        if (isset($data['tags'])) {
            $question->addTags($data['tags']);
        }

        $question->update($data);

        if ($request->has('withExamWrapper')) {
            return app('App\Http\Controllers\Admin\ExamController')
                ->show($question->fresh()->topic->exam);
        }

        return $this->show($question);
    }

    public function destroy(Question $question)
    {
        $question->delete();
        return response(null, 200);
    }
}
