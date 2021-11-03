<?php

namespace App\Http\Controllers;

use App\Models\Stream;
use App\Models\Annotation;
use App\Http\Resources\AnnotationResource;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class AnnotationController extends Controller
{
    public function index(Stream $stream)
    {
      $annotations = Annotation::query()
        ->where('stream_id', $stream->id)
        ->whereNull('parent_id')
        ->orderBy('position', 'ASC')
        ->orderBy('name', 'ASC')
        ->get();

      return AnnotationResource::collection($annotations);
    }

    public function types(Stream $stream)
    {
      $types = DB::select("
        SELECT DISTINCT
          type
        FROM
          annotations
        WHERE
          stream_id = ? AND
          parent_id IS NULL
      ", [ $stream->id ]);

      return array_map(fn($row) => $row->type, $types);
    }

    public function type(Stream $stream, $type)
    {
      $annotations = Annotation::query()
        ->where('stream_id', $stream->id)
        ->whereNull('parent_id')
        ->where('type', $type)
        ->orderBy('position', 'ASC')
        ->orderBy('name', 'ASC')
        ->get();

      return AnnotationResource::collection($annotations);
    }

    public function get(Stream $stream, Annotation $annotation)
    {
      $annotation->load([
        'stream.course',
        'stream.level',
        'children',
        'questions.topic.exam'
      ]);
      $annotation->questions();
      return new AnnotationResource($annotation);
    }

    public function oefensets(Stream $stream)
    {
      $annotations = Annotation::query()
        ->where('stream_id', $stream->id)
        ->where('type', 'oefenset')
        ->orderBy('position', 'ASC')
        ->orderBy('name', 'ASC')
        ->get();

      $annotations->load([
        'children.questions.topic.exam',
        'children.questions.chapters'
      ]);

      return AnnotationResource::collection($annotations);
    }
}
