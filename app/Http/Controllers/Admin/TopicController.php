<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use App\Models\Topic;
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
        ]);

        $topic = $exam->topics()->create($data);

        return new TopicResource($topic);
    }

    public function update(Request $request, Topic $topic)
    {
        $data = $request->validate([
            'introduction' => 'nullable|string',
        ]);

        $topic->update($data);
        $exam = $topic->exam->load('topics');

        return new ExamResource($exam);
    }
}
