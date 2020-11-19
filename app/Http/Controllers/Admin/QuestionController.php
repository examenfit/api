<?php

namespace App\Http\Controllers\Admin;

use App\Models\Topic;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
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
            'answers' => 'array',
            'answers.*.text' => 'required|string',
            'answers.*.points' => 'required|number',
            'attachments' => 'array',
            'attachments.*.id' => ['required', new HashIdExists('attachments')],
        ]);

        $question = $topic->questions()->create($data);

        if ($data['attachments']) {
            $question->addAttachments($data['attachments']);
        }



        // Attach attachments

        return response(null, 200);

        // return new QuestionResource($question);
    }
}
