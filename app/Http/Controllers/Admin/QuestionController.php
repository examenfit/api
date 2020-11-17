<?php

namespace App\Http\Controllers\Admin;

use App\Models\Topic;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;

class QuestionController extends Controller
{
    public function store(Request $request, Topic $topic)
    {
        $data = $request->validate([
            'number' => 'required|integer',
            'points' => 'required|integer',
            'introduction' => 'required|string',
            'text' => 'required|string',
            'answer' => 'array',
            'answer.*.text' => 'required|string',
            'attachments' => 'array',
            'attachments.*.id' => 'required',
        ]);

        // dd($data);

        // Create Question

        // Create answer

        // Attach attachments

        return response(null, 200);

        // return new QuestionResource($question);
    }
}
