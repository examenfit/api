<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;

class ExamsController extends Controller
{
    public function index()
    {
        $exams = Exam::all();

        return ExamResource($exams);
    }
}
