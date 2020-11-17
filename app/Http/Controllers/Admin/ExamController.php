<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::all();

        return ExamResource($exams);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'level' => 'required|in:havo,vwo',
            'year' => 'required|integer|min:2010',
            'term' => 'required|integer|in:1,2',
        ]);

        $exam = Exam::create($data);
        $exam->load('topics');

        return new ExamResource($exam);
    }
}
