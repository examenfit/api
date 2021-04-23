<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tag;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Resources\TagResource;
use App\Http\Controllers\Controller;
use App\Rules\HashIdExists;

class TagController extends Controller
{
    public function index(Course $course)
    {
        $course->load(['tags' => function ($query) {
            $query->with('level')
                ->withCount('question')
                ->orderBy('name', 'ASC');
        }]);

        return TagResource::collection($course->tags);
    }

    public function store(Request $request, Course $course)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'level_id' => ['required', new HashIdExists('levels')],
        ]);

        $course->tags()->create($data);

        return $this->index($course);
    }

    public function update(Request $request, Tag $tag)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'level_id' => ['required', new HashIdExists('levels')],
        ]);

        $tag->update($data);

        return $this->index($tag->course);
    }

    public function destroy(Tag $tag)
    {
        $course = $tag->course;
        $tag->delete();

        return $this->index($course);
    }
}
