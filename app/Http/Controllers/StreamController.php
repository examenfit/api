<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Stream;
use App\Http\Resources\TagResource;
use App\Http\Resources\StreamResource;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Vinkla\Hashids\Facades\Hashids;

class StreamController extends Controller
{
    public function index()
    {
        return array_map(function($row) {
            return [
              'id' => Hashids::encode($row->id),
              'status' => $row->status,
              'slug' => $row->slug,
              'course' => [
                'id' => Hashids::encode($row->course_id),
                'name' => $row->course,
              ],
              'level' => [
                'id' => Hashids::encode($row->level_id),
                'name' => $row->level,
              ],
              'proportion_threshold_low' => $row->proportion_threshold_low,
              'proportion_threshold_high' => $row->proportion_threshold_high,
            ];
        }, DB::select("
            SELECT
              s.id,
              s.slug,
              s.status,
              course_id,
              c.name AS course,
              level_id,
              l.name AS level,
              proportion_threshold_low,
              proportion_threshold_high
            FROM
              streams s,
              courses c,
              levels l
            WHERE
              s.course_id = c.id AND
              s.level_id = l.id
            ORDER BY
              course,
              level
        "));
    }

    public function formuleblad(Stream $stream)
    {
        return [
          'formuleblad' => $stream->formuleblad
        ];
    }

    public function tags(Stream $stream)
    {
        $statuses = "'published'";
        $user = auth()->user();
        $role = $user ? $user->role : '';
        if ($role === 'admin' || $role === 'author') {
          $statuses = "'published','concept'";
        }
        return array_map(fn($row) => [
          'id' => Hashids::encode($row->id),
          'stream_id' => Hashids::encode($row->stream_id),
          'name' => $row->name,
          'topics_count' => $row->topics_count,
          'children' => []
        ], DB::select("
          SELECT
            tags.id,
            tags.stream_id,
            tags.name,
            count(DISTINCT topic_id) AS topics_count
          FROM
            tags,
            question_tag,
            questions,
            topics,
            exams
          WHERE
            tag_id = tags.id AND
            question_id = questions.id AND
            topic_id = topics.id AND
            exam_id = exams.id AND
            exams.status IN ($statuses) AND
            tags.stream_id = ?
          GROUP BY
            tags.id,
            tags.stream_id,
            name
          ORDER BY 
            name
        ", [ $stream->id ]));
/*
        $stream->load(['tags' => function ($query) {
            $query->withCount(['topics']);
        }]);

        return TagResource::collection($stream->tags->sortBy('name'));
*/
    }

    public function tag(Stream $stream, Tag $tag)
    {
        return new TagResource($tag);
    }

    public function allQuestions($stream)
    {
       $id = Hashids::decode($stream)[0];
       return DB::select("
         select
           year,
           term,
           questions.number
         from
           exams, topics, questions
         where
           topic_id = topics.id and
           exam_id = exams.id and
           stream_id = ?
         order by 1,2,3
       ", [$id]);
    }

    // fixme
    public function invalid_tags()
    {
        return DB::select("
            select
              qt.id,
              qt.tag_id,
              t1.stream_id as tag_stream_id,
              e.stream_id as exam_stream_id,
              t1.name
            from
              tags t1,
              exams e,
              questions q,
              question_tag qt,
              topics t
            where t1.id = qt.tag_id
              and e.id = t.exam_id
              and t.id = q.topic_id
              and q.id = qt.question_id
              and e.stream_id != t1.stream_id
            order by name
        ");
    }

    public function fixable_tags()
    {
        return DB::select("
            select
              t1.stream_id as tag_stream_id,
              e.stream_id as exam_stream_id,
              qt.id,
              t1.id,
              t2.id,
              t1.name
            from
              tags t1,
              tags t2,
              exams e,
              questions q,
              question_tag qt,
              topics t
            where t1.id = qt.tag_id
              and e.id = t.exam_id
              and t.id = q.topic_id
              and q.id = qt.question_id
              and t1.name = t2.name
              and e.stream_id != t1.stream_id
              and e.stream_id = t2.stream_id
            order by name
        ");
    }
    public function fix_tags()
    {
        return DB::update("
            update
              tags t1,
              tags t2,
              exams e,
              questions q,
              question_tag qt,
              topics t
            set qt.tag_id = t2.id
            where t1.id = qt.tag_id
              and e.id = t.exam_id
              and t.id = q.topic_id
              and q.id = qt.question_id
              and t1.name = t2.name
              and e.stream_id != t1.stream_id
              and e.stream_id = t2.stream_id
        ");
    }
    public function null_stream_chapters()
    {
        return DB::select("
            select *
            from chapters children
            where stream_id is null
        ");
    }
    public function fix_null_stream_chapters()
    {
        return DB::select("
            update
              chapters parent,
              chapters children
            set children.stream_id = parent.stream_id
            where children.chapter_id = parent.id
              and children.stream_id is null
        ");
    }
    public function invalid_domains()
    {
        return DB::select("
          select
            r.id,
            d.name,
            d.stream_id as domain_stream_id,
            e.stream_id as exam_stream_id
          from
            domains d,
            domain_question r,
            questions q,
            topics t,
            exams e
          where
            d.id = r.domain_id and
            q.id = r.question_id and
            t.id = q.topic_id and
            e.id = t.exam_id and
            (d.stream_id is null or d.stream_id != e.stream_id)
        ");
    }
    public function fixable_domains()
    {
        return DB::select("
          select
            r.id,
            d.name,
            d.stream_id as domain_stream_id,
            e.stream_id as exam_stream_id
          from
            domains d,
            domain_question r,
            questions q,
            topics t,
            exams e
          where
            d.id = r.domain_id and
            q.id = r.question_id and
            t.id = q.topic_id and
            e.id = t.exam_id and
            d.stream_id is null
        ");
    }
    public function fix_domains()
    {
        return DB::update("
          update
            domains d,
            domain_question r,
            questions q,
            topics t,
            exams e
          set
            d.stream_id = e.stream_id
          where
            d.id = r.domain_id and
            q.id = r.question_id and
            t.id = q.topic_id and
            e.id = t.exam_id and
            d.stream_id is null
        ");
    }

    public function Ostep_answer_sections()
    {
        return DB::select("
            select
              questions.id as question_id,
              courses.name as vak,
              levels.name as niveau,
              year,
              term,
              topics.name,
              questions.number
            from
              questions,
              topics,
              exams,
              streams,
              courses,
              levels
            where
              courses.id = streams.course_id and
              levels.id = streams.level_id and
              streams.id = exams.stream_id and
              exams.id = topics.exam_id and
              topics.id = questions.topic_id and
              questions.id in (
                select
                  question_id
                from
                  answers
                where
                  id not in (select answer_id from answer_sections)
              )
              order by 1,2,3,4,6;
        ");
    }

    function fix_answer_sections() {
        return DB::delete("
            delete from
              answers
            where
              id not in (select answer_id from answer_sections)
        ");
    }
}
