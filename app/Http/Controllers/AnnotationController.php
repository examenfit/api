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
        'children.questions.chapters.parent'
      ]);

      return AnnotationResource::collection($annotations);
    }

    private function getQuestion($year, $term, $number)
    {
      $questions = DB::select("
        select
          questions.id,
          topic_id,
          exam_id
        from
          questions, topics, exams
        where
          exams.year = ? and
          exams.term = ? and
          exams.id = exam_id and
          topics.id = topic_id and
          questions.number = ?
      ", [ $year, $term, $number ]);

      foreach($questions as $question) {
        return $question;
      }
    }

    public function putQuestion($annotation, $year, $term, $number)
    {
      $annotation_id = Hashids::decode($annotation)[0];
      $question = $this->getQuestion($year, $term, $number);
      $question_id = $question->id;
      DB::select("
        insert into question_annotation
          (annotation_id, question_id)
        values
          (?, ?)
      ", [ $annotation_id, $question_id ]);
      return [
        'id' => Hashids::encode($question->id),
        'topic_id' => Hashids::encode($question->topic_id),
        'exam_id' => Hashids::encode($question->exam_id),
      ];
    }

    public function deleteQuestion($annotation, $year, $term, $number)
    {
      $annotation_id = Hashids::decode($annotation)[0];
      $question = $this->getQuestion($year, $term, $number);
      $question_id = $question->id;
      DB::select("
        delete from
          question_annotation
        where
          annotation_id = ? and
          question_id = ?
      ", [ $annotation_id, $question_id ]);
      return 202;
    }

    public function addAnnotation(Stream $stream, Request $request)
    {
      $data = $request->validate([
        'parent_id' => 'required',
        'name' => 'required',
      ]);
      $stream_id = $stream->id;
      $parent_id = Hashids::decode($data['parent_id'])[0];
      $name = $data['name'];
      DB::select("
        insert into annotations
          (stream_id, parent_id, name, type)
        values
          (?, ?, ?, 'basisvaardigheid')
      ", [
        $stream_id, $parent_id, $name
      ]);
      return $data;
    }
}
