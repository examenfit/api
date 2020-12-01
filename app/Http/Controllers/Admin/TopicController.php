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
    public function store(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'introduction' => 'nullable|string',
            'attachments' => 'array',
            'attachments.*.id' => ['required', new HashIdExists('attachments')],
        ]);

        $topic = $exam->topics()->create($data);

        if (isset($data['attachments'])) {
            $topic->addAttachments($data['attachments']);
        }

        $exam->load('topics.questions', 'files');

        return new ExamResource($exam);
    }

    public function update(Request $request, Topic $topic)
    {

        $data = $request->validate([
            'name' => 'nullable|string',
            'introduction' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*.id' => ['required', new HashIdExists('attachments')],
        ]);

        $topic->update($data);

        $exam = $topic->exam->load('topics.questions', 'files');

        return new ExamResource($exam);
    }
}
