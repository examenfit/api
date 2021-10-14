<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course;
use App\Models\Stream;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\StreamResource;
use App\Http\Resources\FacetResource;
use Vinkla\Hashids\Facades\Hashids;

class CourseController extends Controller
{
    public function index()
    {
        return CourseResource::collection(Course::all());
    }

    public function show(Stream $stream)
    {
        $stream->load([
            'course',
            'level',
            'exams'
        ]);

        return new StreamResource($stream);
    }

    public function update(Stream $stream, Request $request)
    {
        $stream->formuleblad = $request->formuleblad;
        $stream->save();

        return response()->json([ 'status' => 'ok' ]);
    }

    public function showMeta(Stream $stream)
    {
        $stream->load([
            'tags',
            'domains' => fn($q) => $q->where('parent_id', null),
            'questionTypes',
            'chapters' => fn ($q) => $q->where('chapter_id', null)
                ->orderBy('name')
                ->orderBy('methodology_id'),
        ]);

        return new StreamResource($stream);
    }
}
