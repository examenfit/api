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
        $topic->load([
            'exam',
            'questions',
            'questions.attachments',
            'questions.appendixes',
        ]);
        return new TopicResource($topic);
    }

    public function store(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'introduction' => 'nullable|string',
            'position' => 'nullable|integer',
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
            'position' => 'nullable|integer',
            'attachments' => 'nullable|array',
            'attachments.*.id' => ['required', new HashIdExists('attachments')],
        ]);

        $topic->update($data);

        if (isset($data['attachments'])) {
            $topic->addAttachments($data['attachments']);
        }

        if ($request->has('withExamWrapper')) {
            return app('App\Http\Controllers\Admin\ExamController')
                ->show($topic->exam);
        }

        return $this->show($topic->fresh());
    }

    public function destroy(Topic $topic)
    {
        $topic->delete();

        return response(null, 200);
    }

    public function cache()
    {
        Artisan::call('ef:cache:topics');
        return response(null, 200);
    }
}
