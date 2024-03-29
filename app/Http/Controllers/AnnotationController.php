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

    public function examens(Stream $stream)
    {

      $annotations = Annotation::query()
        ->where('stream_id', $stream->id)
        ->where('type', 'examen')
        ->orderBy('position', 'ASC')
        ->with([ 'children' => function($query) {
          $query->orderBy('position', 'ASC');
        }])
        ->get();

      $annotations->load([
        'children.questions.topic.exam',
        'children.questions.chapters.parent'
      ]);

      return AnnotationResource::collection($annotations);
    }

    private function getQuestion($stream, $year, $term, $number)
    {
      $questions = DB::select("
        select
          questions.id,
          topic_id,
          exam_id
        from
          questions, topics, exams
        where
          exams.stream_id = ? and
          exams.year = ? and
          exams.term = ? and
          exams.id = exam_id and
          topics.id = topic_id and
          questions.number = ?
      ", [ $stream, $year, $term, $number ]);

      foreach($questions as $question) {
        return $question;
      }
    }

    public function putQuestion(Annotation $annotation, $year, $term, $number)
    {
      $question = $this->getQuestion($annotation->stream_id, $year, $term, $number);
      $question_id = $question->id;
      DB::select("
        insert into question_annotation
          (annotation_id, question_id)
        values
          (?, ?)
      ", [ $annotation->id, $question_id ]);
      return [
        'id' => Hashids::encode($question->id),
        'topic_id' => Hashids::encode($question->topic_id),
        'exam_id' => Hashids::encode($question->exam_id),
      ];
    }

    public function deleteQuestion(Annotation $annotation, $year, $term, $number)
    {
      $question = $this->getQuestion($annotation->stream_id, $year, $term, $number);
      $question_id = $question->id;
      DB::select("
        delete from
          question_annotation
        where
          annotation_id = ? and
          question_id = ?
      ", [ $annotation->id, $question_id ]);
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

    public function createExams() {
      DB::insert("
        delete from question_annotation
        where annotation_id in (select id from annotations where type = 'opgave')
      ");
      DB::insert("
        delete from annotations
        where type in ('examen', 'opgave')
      ");
      $exams = DB::select("
        select year, term, stream_id, id
        from exams
        where status = 'published' and show_answers
        order by year desc, term asc
      ");
      $position = time();
      foreach($exams as $exam) {
        $group = Annotation::create([
          'stream_id' => $exam->stream_id,
          'position' => $position++,
          'name' => $exam->year . ' ' . $exam->term . 'e tijdvak',
          'type' => 'examen',
        ]);
        $topics = DB::select("
          select position, name, id
          from topics
          where exam_id = ?
          order by position asc
        ", [ $exam->id ]);
        foreach($topics as $topic) {
          $annotation = Annotation::create([
            'stream_id' => $exam->stream_id,
            'parent_id' => $group->id,
            'position' => $position++,
            'name' => $topic->name,
            'type' => 'opgave',
          ]);
          $questions = DB::select("
            select number, id
            from questions
            where topic_id = ?
            order by number asc
          ", [ $topic->id ]);
          foreach($questions as $question) {
            DB::insert("
              insert into question_annotation
              set annotation_id = ?, question_id = ?, position = ?
            ", [ $annotation->id, $question->id, $position++ ]);
          }
        }
      }
      return 'ok';
    }
}
