<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Resources\CourseResource;
use Vinkla\Hashids\Facades\Hashids;

class CourseController extends Controller
{
    public function index()
    {
        return CourseResource::collection(Course::all());
    }
}
