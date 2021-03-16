<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;

class IndexController extends Controller
{
    public function index()
    {
        $courses = Course::with([
            'exams' => function ($query) {
                $query->with('topics.questions')->orderBy('year', 'DESC')->orderBy('term', 'DESC');
            }
        ])->get();

        return CourseResource::collection($courses);
    }
}
