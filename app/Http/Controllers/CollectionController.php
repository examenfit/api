<?php

namespace App\Http\Controllers;

use DateTime;
use DateTimeZone;

use Exception;

use App\Models\Topic;
use App\Models\Course;
use App\Models\Question;
use App\Models\Collection;
use App\Models\Group;
use App\Models\Elaboration;

use App\Models\License;
use App\Models\Seat;
use App\Models\Privilege;

use App\Rules\HashIdExists;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use App\Http\Resources\CollectionResource;
use App\Support\CollectionQuestionsDocument;
use App\Support\CollectionCorrectionsDocument;
use App\Support\DocumentMarkup;

class CollectionController extends Controller
{
    public function constraints(Course $course)
    {
        $id = auth()->user()->id;

        return DB::select("
          select
          /*
            stream_id,
          */
            cast(sum(complete_topics) as unsigned) as complete_topics,
            cast(sum(complete_topics + partial_topics) as unsigned) as topics,
            cast(sum(if (download_type = 'word', complete_topics + partial_topics, 0)) as unsigned) as word_topics
          from
          /*
            exams,
            topics,
            questions,
          */
            collections
          where
          /*
            exam_id = exams.id and
            topic_id = topics.id and
            question_id = questions.id and
          */
            user_id = ? and
            course_id =? and
            created_at > date_add(now(), interval -7 day)
        ", [ $id, $course->id ]);
    }

    public function activity_summary(Collection $collection)
    {
        $user = auth()->user();
        if ($user === 'leerling') { 
          return response()->json([
            'devices' => 0,
            'sessions' => 0,
            'last' => '2000-01-01',
            'activities' => 0
          ]);
        }

        $r = DB::select("
          select
            count(distinct device_key) as devices,
            count(distinct session_key) as sessions,
            date(max(created_at)) as last,
            count(*) as activities
          from
            activity_logs
          where
            collection_id = ?
           and
            email is null
        ", [ $collection->id ]);
        return response()->json($r[0]);
    }

    public function unknown_usage()
    {
        return DB::select("
            select *
            from collections
            where complete_topics is null
        ");
    }

    public function fix_usage()
    {
        $collections = [];
        $usage = DB::select("
            select
              collection_id,
              topic_id,
              count(distinct q.id) as questions_used,
              (select count(*) from questions m where m.topic_id = q.topic_id) as questions_available
            from
              questions q,
              collection_question r
            where
              q.id = question_id
            group by
              collection_id,
              topic_id
            order by
              collection_id,
              topic_id
        ");
        foreach($usage as $info) {
            $id = $info->collection_id;
            if (!array_key_exists("x$id", $collections)) {
                $collections["x$id"] = [
                  'id' => $id,
                  'partial' => 0,
                  'complete' => 0,
                ];
            }
            if ($info->questions_used === $info->questions_available) {
                $collections["x$id"]['partial'] += 0;
                $collections["x$id"]['complete'] += 1;
            } else {
                $collections["x$id"]['partial'] += 1;
                $collections["x$id"]['complete'] += 0;
            }
        }

        foreach($collections as $usage) {
            $id = $usage['id'];
            $partial = $usage['partial'];
            $complete = $usage['complete'];
            DB::update("
              update collections
              set partial_topics = ?,
                  complete_topics = ?
              where id = ?
            ", [ $partial, $complete, $id ]);
        }
        
        return response()->json([ 'status' => 'ok' ]);
    }

    public function show(Collection $collection, Topic $topic)
    {
        $user = auth()->user();
        $role = $user ? $user->role : '';
        $isAuthor = $role === 'admin' || $role === 'author';
        $statuses = $isAuthor ? [ 'published', 'concept' ] : [ 'published' ];

        $load = [
            'author',
            'questions' => function ($query) use ($topic) {
                if ($topic->id) {
                    $query->where('topic_id', $topic->id);
                }

                $query->orderBy('topic_id', 'ASC')
                    ->orderBy('number', 'ASC');
            },
            'questions.tips',
            'questions.topic.attachments',
            'questions.attachments',
            'questions.tags',
            'questions.dependencies',
            'questions.chapters.methodology',
            'questions.chapters.parent',
        ];

        if ($collection->questions[0]->topic->exam->show_answers) {
          $load[] = 'questions.answers.sections.tips';
        }

        $collection->load($load);

        return new CollectionResource($collection);
    }

    public function showCollectionQuestionsDocument(Request $request, Collection $collection)
    {
        $name = preg_replace('/[\/\\\\]+/', ' ', $collection->name);
        $filename = "ExamenFit opgaven {$name}.docx";
        $file = storage_path("app/public/collections/{$collection->hash_id}.docx");

        $document = new CollectionQuestionsDocument();
        $document->showAppendixes();
        $document->createDocument($collection);
        $document->saveDocument($file, 'docx');
        return response()->download($file, $filename);
    }

    public function showCollectionCorrectionsDocument(Request $request, Collection $collection)
    {
        $name = preg_replace('/[\/\\\\]+/', ' ', $collection->name);
        $filename = "ExamenFit correctievoorschrift {$name}.docx";
        $file = storage_path("app/public/collections/{$collection->hash_id}.docx");

        $document = new CollectionCorrectionsDocument();
        $document->createDocument($collection);
        $document->saveDocument($file, 'docx');
        return response()->download($file, $filename);
    }

    public function showCollectionQuestionsPdf(Request $request, Collection $collection)
    {
        Log::info('showCollectionQuestionsPdf');

        $api = url("/api/download-collection-html");
        $api = str_replace("http://localhost:8000", "https://staging-api.examenfit.nl", $api);

        Log::info("api=$api");

        $server = config('app.examenfit_scripts_url');

        $hash = $collection->hash_id; // note: this name conflicts with the appendixes pdf
        $pdf = $hash.".pdf";          //       but as they are not downloaded at the same time
        $tmp = "/tmp/".$pdf;          //       there _should_ be no problem

        Log::info("pdf=$pdf");
        Log::info("tmp=$tmp");

        // currently ssh authentication goes with public key authentication
        // in the future this may need to become ssh -i id_rsa or something alike
        $generate = "ssh examenfit@$server make/pdf $hash $api";
        $retrieve = "scp examenfit@$server:pdf/$pdf $tmp";

        Log::info($generate);
        shell_exec($generate);

        Log::info($retrieve);
        shell_exec($retrieve);

        Log::info("response");
        $name = preg_replace('/[\/\\\\]+/', ' ', $collection->name);
        $filename = "ExamenFit opgaven {$name}.pdf";
        return response()->download($tmp, $filename);
    }

    public function showCollectionQuestionsHtml(Request $request, Collection $collection)
    {
        $app_url = config('app.dashboard_url');
        $markup = new DocumentMarkup();

        $collection->load([
            'author',
            'questions' => fn ($q) => $q->orderBy('topic_id', 'ASC')->orderBy('number', 'ASC'),
            'questions.attachments',
            'questions.topic',
            'questions.topic.attachments',
            'questions.topic.exam',
            'questions.topic.exam.stream.course',
            'questions.topic.exam.stream.level',
            'questions.dependencies',
            'questions.answers.sections',
        ]);

        $topic_id = -1;
        $points = 0;
        $time_in_minutes = 0;

        $topics = [];
        $questions = [];

        $use_text = [];
        $use_introduction = [];
        $use_attachments = [];
        $use_appendixes = [];

        foreach ($collection['questions'] as $question) {

            $points += $question['points'];
            $time_in_minutes += $question['time_in_minutes'];

            $id = $question['id'];
            $use_text[$id] = true;
            $use_introduction[$id] = true;
            $use_attachments[$id] = true;
            $use_appendixes[$id] = true;

            foreach ($question['dependencies'] as $dependency) {
                $pivot = $dependency['pivot'];
                $id = $pivot['question_id'];

                if ($pivot['introduction']) $use_introduction[$id] = true;
                if ($pivot['attachments']) $use_attachments[$id] = true;
                if ($pivot['appendixes']) $use_appendixes[$id] = true;
            }

            $topic = $question['topic'];
            if ($topic['id'] !== $topic_id) {
                $topics[] = $topic;
                $topic_id = $topic['id'];
            }

            $question['has_answers'] = $topic['has_answers'];
            $questions[] = $question;
        }

        $appendixes = [];
        $appendix_added = [];

        $topic_order = $collection['topic_order'] ?: '';
        usort($topics, function ($a, $b) use ($topic_order) {
          $i = Hashids::encode($a['id']);
          $j = Hashids::encode($b['id']);
          return strpos($topic_order, $i) - strpos($topic_order, $j);
        });

        foreach ($topics as $topic) {
            $topic['introduction'] = $markup->fix($topic['introduction']);

            foreach ($topic['questions'] as $question) {
                $id = $question['id'];

                $question['use_text'] = array_key_exists($id, $use_text);
                $question['use_introduction'] = array_key_exists($id, $use_introduction);
                $question['use_attachments'] = array_key_exists($id, $use_attachments);

                if (array_key_exists($id, $use_appendixes)) {
                    foreach ($question['appendixes'] as $appendix) {
                        $id = $appendix->id;
                        if (array_key_exists($id, $appendix_added)) {
                            /* skip */
                        } else {
                            $appendixes[] = $appendix;
                            $appendix_added[$id] = true;
                        }
                    }
                }

                $question['introduction'] = $markup->fix($question['introduction']);
                $question['text'] = $markup->fix($question['text']);

                $c = $collection->hash_id;
                $q = $question->hash_id;
                $t = $topic->hash_id;

                $question['url'] = "$app_url/c/{$c}/{$t}/{$q}";
                
                foreach ($question['answers'] as $answer) {
                  foreach ($answer['sections']  as $section) {
                    $section['correction'] = $markup->fix($section['correction']);
                  }
                }
            }
        }

        $formuleblad = $topics[0]->exam->stream->formuleblad;

        $collection['formuleblad'] = $markup->fix($formuleblad);
        $collection['topics'] = $topics;
        $collection['questions'] = $questions;
        $collection['points'] = $points;
        $collection['time_in_minutes'] = $time_in_minutes;

        $collection['appendixes'] = $appendixes;

        date_default_timezone_set('CET');
        $timestamp = date('Y-m-d H:i');

        $collection['timestamp'] = $timestamp;

        //return response()->json($collection);

        return view('pdf', $collection);
    }

    public function showCollectionAppendixesPdf(Request $request, Collection $collection)
    {
        Log::info('showCollectionAppendixesPdf');

        $api = url("/api/download-appendixes-html");
        $api = str_replace("http://localhost:8000", "https://staging-api.examenfit.nl", $api);

        Log::info("api=$api");

        $server = config('app.examenfit_scripts_url');

        $hash = $collection->hash_id; // note: this name conflicts with the collections pdf
        $pdf = $hash.".pdf";          //       but as they are not downloaded at the same time
        $tmp = "/tmp/".$pdf;          //       there _should_ be no problem

        Log::info("pdf=$pdf");
        Log::info("tmp=$tmp");

        // currently ssh authentication goes with public key authentication
        // in the future this may need to become ssh -i id_rsa or something alike
        $generate = "ssh examenfit@$server make/pdf $hash $api";
        $retrieve = "scp examenfit@$server:pdf/$pdf $tmp";

        Log::info($generate);
        shell_exec($generate);

        Log::info($retrieve);
        shell_exec($retrieve);

        Log::info("response");
        $name = preg_replace('/[\/\\\\]+/', ' ', $collection->name);
        $filename = "ExamenFit uitwerkbijlages {$name}.pdf";
        return response()->download($tmp, $filename);
    }

    public function showCollectionAppendixesHtml(Request $request, Collection $collection)
    {
        $app_url = config('app.dashboard_url');
        $markup = new DocumentMarkup();

        $collection->load([
            //'author',
            'questions' => fn ($q) => $q->orderBy('topic_id', 'ASC')->orderBy('number', 'ASC'),
            //'questions.attachments',
            //'questions.topic',
            //'questions.topic.attachments',
            //'questions.topic.exam',
            //'questions.topic.exam.stream.course',
            //'questions.topic.exam.stream.level',
            'questions.dependencies',
            //'questions.answers.sections',
        ]);

        $topic_id = -1;
        //$points = 0;
        //$time_in_minutes = 0;

        $topics = [];
        $questions = [];

        //$use_text = [];
        //$use_introduction = [];
        //$use_attachments = [];
        $use_appendixes = [];

        foreach ($collection['questions'] as $question) {

            //$points += $question['points'];
            //$time_in_minutes += $question['time_in_minutes'];

            $id = $question['id'];
            //$use_text[$id] = true;
            //$use_introduction[$id] = true;
            //$use_attachments[$id] = true;
            $use_appendixes[$id] = true;

            foreach ($question['dependencies'] as $dependency) {
                $pivot = $dependency['pivot'];
                $id = $pivot['question_id'];

                //if ($pivot['introduction']) $use_introduction[$id] = true;
                //if ($pivot['attachments']) $use_attachments[$id] = true;
                if ($pivot['appendixes']) $use_appendixes[$id] = true;
            }

            $topic = $question['topic'];
            if ($topic['id'] !== $topic_id) {
                $topics[] = $topic;
                $topic_id = $topic['id'];
            }

            //$question['has_answers'] = $topic['has_answers'];
            $questions[] = $question;
        }

        $appendixes = [];
        $appendix_added = [];

        foreach ($topics as $topic) {
            //$topic['introduction'] = $markup->fix($topic['introduction']);

            foreach ($topic['questions'] as $question) {
                $id = $question['id'];

                //$question['use_text'] = array_key_exists($id, $use_text);
                //$question['use_introduction'] = array_key_exists($id, $use_introduction);
                //$question['use_attachments'] = array_key_exists($id, $use_attachments);

                if (array_key_exists($id, $use_appendixes)) {
                    foreach ($question['appendixes'] as $appendix) {
                        $id = $appendix->id;
                        if (array_key_exists($id, $appendix_added)) {
                            /* skip */
                        } else {
                            $appendixes[] = $appendix;
                            $appendix_added[$id] = true;
                        }
                    }
                }

                //$question['introduction'] = $markup->fix($question['introduction']);
                //$question['text'] = $markup->fix($question['text']);

                //$c = $collection->hash_id;
                //$q = $question->hash_id;
                //$t = $topic->hash_id;

                //$question['url'] = "$app_url/c/{$c}/{$t}/{$q}";
                
                //foreach ($question['answers'] as $answer) {
                  //foreach ($answer['sections']  as $section) {
                    //$section['correction'] = $markup->fix($section['correction']);
                  //}
                //}
            }
        }

        //$formuleblad = $topics[0]->exam->stream->formuleblad;

        //$collection['formuleblad'] = $markup->fix($formuleblad);
        //$collection['topics'] = $topics;
        //$collection['questions'] = $questions;
        //$collection['points'] = $points;
        //$collection['time_in_minutes'] = $time_in_minutes;

        $collection['appendixes'] = $appendixes;

        date_default_timezone_set('CET');
        $timestamp = date('Y-m-d H:i');

        $collection['timestamp'] = $timestamp;

        return view('appendixes', $collection);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'download_type' => 'required|string',
            'topic_order' => 'string|nullable',
            'course_id' => ['required', new HashIdExists('courses')],
            'questions' => 'required|array',
            'questions.*' => new HashIdExists('questions'),
        ]);

        $collection = Collection::create([
            'name' => $data['name'],
            'course_id' => Hashids::decode($data['course_id'])[0],
            'download_type' => $data['download_type'],
            'topic_order' => $data['topic_order'],
        ]);

        $collection->questions()->sync(
            collect($data['questions'])->map(
                fn ($q) => Hashids::decode($q)[0],
            )
        );

        $info = DB::select("
            select
              topic_id,
              count(distinct q.id) as questions_used,
              (select count(*) from questions m where m.topic_id = q.topic_id) as questions_available
            from
              questions q,
              collection_question r
            where
              q.id = question_id and
              collection_id = ?
            group by
              topic_id
        ", [ $collection->id ]);

        $partial_topics = 0;
        $complete_topics = 0;
        foreach ($info as $row) {
          if ($row->questions_used < $row->questions_available) {
            ++$partial_topics;
          } else {
            ++$complete_topics;
          }
        }

        $collection->load('topics');
        $collection->partial_topics = $partial_topics;
        $collection->complete_topics = $complete_topics;
        $collection->save();

        return new CollectionResource($collection);
    }

    function hideCollectionFromSeat($collection, $seat, $until) {
      $action = 'vraag verbergen';
      foreach($collection->questions as $question) {
        $id = $question->id;
        foreach($seat->privileges as $priv) {
          $hasAction = $priv->action === $action;
          $hasObject = $priv->object_id === $id;
          if ($hasAction && $hasObject) {
            return 0;
          }
        }
        Privilege::create([
          'actor_seat_id' => $seat->id,
          'action' => $action,
          'object_type' => 'question',
          'object_id' => $id,
          'begin' => $seat->license->begin,
          'end' => $until,
        ]);
      }
      return 1;
    }

    public function hideCollectionFromGroup(Collection $collection, Group $group, Request $request)
    {
        $until = $request->until;
        $n = 0;
        foreach($group->seats as $leerling) {
          $n += $this->hideCollectionFromSeat($collection, $leerling, $until);
        }
        return response()->json([
          'status' => 'ok',
          'hidden' => $n
        ]);
    }

    public function hideCollection(Collection $collection, Request $request)
    {
        $until = $request->until;
        $user_id = $collection->user_id;
        $seats = Seat::query()
          ->where('role', 'docent')
          ->where('user_id', $user_id);

        $n = 0;
        foreach($seats->get() as $docent) {
          $docent->load('license.seats');
          $leerlingen = Seat::query()
            ->where('role', 'leerling')
            ->where('license_id', $docent->license_id);
          foreach($leerlingen->get() as $leerling) {
            $n += $this->hideCollectionFromSeat($collection, $leerling, $until);
          }
        }
        return response()->json([
          'status' => 'ok',
          'hidden' => $n
        ]);
    }

    function shareCollectionWithSeat($collection, $seat) {
      $id = $collection->id;
      foreach($seat->privileges as $priv) {
        $hasAction = $priv->action === 'opgavenset uitvoeren';
        $hasObject = $priv->object_id === $id;
        if ($hasAction && $hasObject) {
          return 0;
        }
      }
      Privilege::create([
        'actor_seat_id' => $seat->id,
        'action' => 'opgavenset uitvoeren',
        'object_type' => 'collection',
        'object_id' => $collection->id,
        'begin' => $seat->license->begin,
        'end' => $seat->license->end
      ]);
      return 1;
    }

    public function shareCollectionWithGroup(Collection $collection, Group $group)
    {
        $n = 0;
        foreach($group->seats as $leerling) {
          $n += $this->shareCollectionWithSeat($collection, $leerling);
        }
        return response()->json([
          'status' => 'ok',
          'shared' => $n
        ]);
    }

    public function shareCollection(Collection $collection)
    {
        $user_id = $collection->user_id;
        $seats = Seat::query()
          ->where('role', 'docent')
          ->where('user_id', $user_id);

        $n = 0;
        foreach($seats->get() as $docent) {
          $docent->load('license.seats');
          $leerlingen = Seat::query()
            ->where('role', 'leerling')
            ->where('license_id', $docent->license_id);
          foreach($leerlingen->get() as $leerling) {
            $n += $this->shareCollectionWithSeat($collection, $leerling);
          }
        }
        return response()->json([
          'status' => 'ok',
          'shared' => $n
        ]);
    }

    public function storeElaboration(Request $request, Collection $collection, Question $question)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png',
            'name' => 'nullable|string',
        ]);

        Elaboration::create([
            'collection_id' => $collection->id,
            'question_id' => $question->id,
            'name' => $data['name'],
            'file_path' => $data['file']->store("collections/{$collection->hash_id}/elaborations"),
        ]);

        return response()->isSuccessful();
    }

    public function latest(Request $request)
    {
        $request->validate([
            'count' => 'integer|min:0'
        ]);
        $count = $request->query('count', 100);
        $user_id = auth()->user()->id;

        return array_map(function($collection) {
          return [
            'id' => Hashids::encode($collection->id),
            'name' => $collection->name,
            'download_type' => $collection->download_type,
            'topic_order' => $collection->topic_order,
            'date' => (new DateTime($collection->created_at))->format('c'),
            'topics' => array_map(function($topic) {
                return [
                  'id' => Hashids::encode($topic->id),
                  'name' => $topic->topic,
                  'questions' => json_decode($topic->topic_data)->questionCount,
                  'points' => (int)$topic->points,
                  'has_answers' => (int)$topic->has_answers,
                  'time_in_minutes' => (int)$topic->time_in_minutes,
                  'selected' => array_map(function($id) {
                    return Hashids::encode($id);
                  }, explode(',', $topic->selected)),
                  'exam' => [
                    'id' => Hashids::encode($topic->exam_id),
                    'level' => $topic->level,
                    'course' => $topic->course,
                    'year' => $topic->year,
                    'term' => $topic->term,
                  ]
                ];
              }, DB::select("
                select
                  sum(q.points) as points,
                  sum(q.time_in_minutes) as time_in_minutes,
                  group_concat(q.id) as selected,
                  t.name as topic,
                  t.has_answers as has_answers,
                  t.cache as topic_data,
                  t.id,
                  t.exam_id,
                  e.year as year,
                  e.term as term,
                  l.name as level,
                  c.name as course
                from
                  collection_question cq,
                  questions q,
                  topics t,
                  exams e,
                  courses c,
                  levels l,
                  streams s
                where
                  ? = cq.collection_id and
                  q.id = cq.question_id and
                  t.id = q.topic_id and
                  e.id = t.exam_id and
                  c.id = s.course_id and
                  l.id = s.level_id and
                  s.id = e.stream_id
                group by
                  t.id,
                  t.name, t.has_answers, t.cache, t.exam_id, e.year, e.term, l.name, c.name
                order by t.id
              ", [ $collection->id ]))
          ];
        }, DB::select("
          select
            id, name, created_at, download_type, topic_order
          from
            collections
          where
            user_id = ?
          order by 1 desc
          limit ?
        ", [ $user_id, $count ]));
    }
}
