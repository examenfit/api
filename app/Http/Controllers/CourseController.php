<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Resources\TagResource;
use App\Http\Resources\TopicResource;

class CourseController extends Controller
{
    public function showTags(Course $course)
    {
        $course->load(['tags' => function ($query) {
            $query->withCount('topics');

            if (request()->get('level') === 'vwo') {
                $query->where('is_vwo', true);
            } else {
                $query->where('is_havo', true);
            }
        }]);

        return TagResource::collection($course->tags);
    }

    public function showTag(Course $course, Tag $tag)
    {
        return new TagResource($tag);

        return TopicResource::collection($topics);
    }
}
