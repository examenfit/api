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
        $question->load([
            'topic',
            'attachments',
            'tags',
            'domains',
            'answers.sections',
            'methodologies',
        ]);

        return new QuestionResource($question);
    }

    public function store(Request $request, Topic $topic)
    {
        $data = $request->validate([
            'number' => 'required|integer',
            'points' => 'required|integer',
            'proportion_value' => 'nullable|integer|min:0',
            'introduction' => 'nullable|string',
            'text' => 'required|string',
            'answerSections' => 'array',
            'answerSections.*.correction' => 'required|string',
            'answerSections.*.points' => 'required|integer',
            'answer_remark' => 'nullable|string',
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
                'remark' => $data['answer_remark'] ?? null,
            ]);

            $answer->sections()->createMany($data['answerSections']);
        }

        if (isset($data['tags'])) {
            $question->addTags($data['tags']);
        }

        if (isset($data['domains'])) {
            $question->addDomains($data['domains']);
        }

        return app('App\Http\Controllers\Admin\ExamController')
            ->show($topic->exam);
    }

    public function update(Request $request, Question $question)
    {
        $data = $request->validate([
            'topic_id' => ['required', new HashIdExists('topics')],
            'number' => 'required|integer',
            'points' => 'required|integer',
            'time_in_minutes' => 'nullable|integer',
            'complexity' => 'nullable|in:low,average,high',
            'proportion_value' => 'nullable|numeric',
            'introduction' => 'nullable|string',
            'text' => 'required|string',
            'answerSections' => 'array',
            'answerSections.*.correction' => 'required|string',
            'answerSections.*.points' => 'required|integer',
            'answer_remark' => 'nullable|string',
            'type_id' => ['required', new HashIdExists('question_types')],
            'tags.*.id' => ['required', new HashIdExists('tags')],
            'domains.*.id' => ['required', new HashIdExists('domains')],
            'methodologies.*.id' => ['required', new HashIdExists('methodologies')],
            'methodologies.*.chapter' => ['required', 'string'],
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
                'remark' => $data['answer_remark'] ?? null,
            ]);

            $answer->sections()->createMany($data['answerSections']);
        }

        if (isset($data['tags'])) {
            $question->addTags($data['tags']);
        }

        if (isset($data['domains'])) {
            $question->addDomains($data['domains']);
        }

        if (isset($data['methodologies'])) {
            $question->addMethodologies($data['methodologies']);
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
