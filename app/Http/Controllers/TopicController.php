<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\Request;
use App\Http\Resources\TopicResource;

class TopicController extends Controller
{
    public function show(Topic $topic)
    {
        $topic->load([
            'highlights',
            'exam.course',
            'questions.attachments',
            'questions.domains.parent',
            'questions.questionType',
            'questions.answers.sections',
            'questions.tags',
            'questions.methodologies',
        ]);

        return new TopicResource($topic);
    }
}
