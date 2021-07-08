<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course;
use App\Models\Stream;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\StreamResource;

class IndexController extends Controller
{
    public function index()
    {
        $streams = Stream::with([
            'course',
            'level',
            'exams' => function ($query) {
                $query->with('topics.questions')->orderBy('year', 'DESC')->orderBy('term', 'DESC');
            }
        ])->get();

        return StreamResource::collection($streams);
/*
        $courses = Course::with([
            'exams' => function ($query) {
                $query->with('topics.questions')->orderBy('year', 'DESC')->orderBy('term', 'DESC');
            }
        ])->get();

        return CourseResource::collection($courses);
*/
    }
}
