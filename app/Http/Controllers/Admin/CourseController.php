<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\FacetResource;

class CourseController extends Controller
{
    public function index()
    {
        return CourseResource::collection(Course::all());
    }

    public function show(Course $course)
    {
        $course->load(['exams', 'facets.children']);

        return new CourseResource($course);
    }

    public function showMeta(Course $course)
    {
        $course->load('tags', 'domains', 'questionTypes', 'methodologies');

        return new CourseResource($course);
    }
}
