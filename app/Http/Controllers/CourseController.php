<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Resources\TagResource;
use App\Http\Resources\TopicResource;
use Vinkla\Hashids\Facades\Hashids;

class CourseController extends Controller
{
    public function showTags(Course $course)
    {
        $course->load(['tags' => function ($query) {
            $query->withCount(['topics' => function ($query) {
                $query->whereHas('exam', function ($query) {
                    $query->where('level', request()->get('level') === "dNRlx" ? "havo" : "vwo");
                });
            }])->orderBy('name');

            $query->where('level_id', Hashids::decode(request()->get('level'))[0]);
        }]);

        return TagResource::collection($course->tags);
    }

    public function showTag(Course $course, Tag $tag)
    {
        return new TagResource($tag);

        return TopicResource::collection($topics);
    }
}
