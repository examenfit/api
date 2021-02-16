<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use App\Models\Topic;
use App\Rules\HashIdExists;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use App\Http\Resources\TopicResource;

class TopicController extends Controller
{
    public function show(Topic $topic)
    {
        $topic->load('exam', 'questions', 'highlights');
        return new TopicResource($topic);
    }

    public function store(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'introduction' => 'nullable|string',
            'attachments' => 'array',
            'attachments.*.id' => ['required', new HashIdExists('attachments')],
        ]);

        if ($exam->topics()->count() === 0) {
            $exam->update(['status' => 'processing']);
        }

        $topic = $exam->topics()->create($data);

        if (isset($data['attachments'])) {
            $topic->addAttachments($data['attachments']);
        }

        if ($request->has('withExamWrapper')) {
            return app('App\Http\Controllers\Admin\ExamController')
                ->show($exam);
        }

        return $this->show($topic);
    }

    public function update(Request $request, Topic $topic)
    {

        $data = $request->validate([
            'complexity' => 'nullable|in:low,average,high',
            'popularity' => 'nullable|numeric|max:5',
            'name' => 'nullable|string',
            'introduction' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*.id' => ['required', new HashIdExists('attachments')],
            'highlights.*.text' => ['required', 'string', 'max:255'],
        ]);

        $topic->update($data);

        if (isset($data['attachments'])) {
            $topic->addAttachments($data['attachments']);
        }

        if ($request->has('withExamWrapper')) {
            return app('App\Http\Controllers\Admin\ExamController')
                ->show($topic->exam);
        }

        $topic->highlights()->delete();

        if (isset($data['highlights'])) {
            $topic->highlights()->createMany(
                collect($data['highlights'])
            );
        }

        return $this->show($topic->fresh());
    }

    public function destroy(Topic $topic)
    {
        $topic->delete();

        return response(null, 200);
    }
}
