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
            'exam.course',
            'questions.attachments',
            'questions.domains.parent',
            'questions.questionType',
            'questions.answers.sections',
            'questions.tags',
        ]);

        return new TopicResource($topic);
    }
}
