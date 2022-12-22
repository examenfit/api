<?php

namespace App\Http\Controllers;

use Mail;
use App\Mail\FeedbackMail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Vinkla\Hashids\Facades\Hashids;


class FeedbackController extends Controller
{
  public function streams()
  {
    return array_map(
      fn($row) => [
        'label' => $row->label,
        'value' => $row->id ? Hashids::encode($row->id) : NULL
      ],
      DB::select("
        SELECT
          CONCAT(courses.name, ' ', levels.name) AS label,
          streams.id AS id
        FROM
          streams, courses, levels
        WHERE
          streams.course_id = courses.id AND
          streams.level_id = levels.id
        ORDER BY
          label
      ")
    );
  }

  public function exams($stream)
  {
    $stream_id = Hashids::decode($stream)[0];
    return array_map(
      fn($row) => [
        'label' => $row->label,
        'value' => $row->id ? Hashids::encode($row->id) : NULL
      ],
      DB::select("
        SELECT
          CONCAT(year, ' ', term, 'e tijdvak') AS label,
          exams.id AS id
        FROM
          exams
        WHERE
          exams.status = 'published' AND
          exams.stream_id = ?
        ORDER BY
          label
      ", [ $stream_id ])
    );
  }

  public function questions($stream, $exam)
  {
    $stream_id = Hashids::decode($stream)[0];
    $exam_id = Hashids::decode($exam)[0];

    return array_map(
      fn($row) => [
        'label' => $row->label,
        'value' => $row->id ? Hashids::encode($row->id) : NULL,
        'question_id' => $row->id
      ],
      DB::select("
        SELECT
          concat(name, ', vraag ', number) AS label,
          questions.id AS id,
          questions.number AS number
        FROM
          questions,
          topics
        WHERE
          topic_id = topics.id AND
          exam_id = ?
        ORDER BY
          number
      ", [ $exam_id ])
    );
  }

  private function answer_parts($question_id, $id)
  {
    $parts = [
      [ 'label' => 'Vraag', 'value' => NULL ],
    ];

    $tips = DB::select("
      SELECT
        id, text
      FROM
        tips
      WHERE
        tippable_type LIKE '%Question%' AND
        tippable_id = ?
    ", [ $question_id ]);

    $t = 1;
    foreach ($tips as $tip)
    {
      $name = $t == 1 ? 'Algemene tip' : $t.'e algemene tip';
      $tip_id = Hashids::encode($tip->id);
      $parts[] = [
        'value' => 'tip#'.$tip_id,
        'label' => $name,
        'info' => $tip->text,
        'tip_id' => $tip_id,
      ];
      $t++;
    }

    $steps = DB::select("
      SELECT
        id
      FROM
        answer_sections
      WHERE
        answer_id = ?
    ", [ $id ]);

    $s = 1;
    $n = count($steps);
    foreach ($steps as $step)
    {
      $name = $n == 1 ? 'Antwoord' : 'Tussenantwoord '.$s;
      $step_id = Hashids::encode($step->id);
      $parts[] = [
        'value' => 'answer_section#'.$step_id,
        'label' => 'Tussenantwoord '.$s,
      ];

      $tips = DB::select("
        SELECT
          id, text
        FROM
          tips
        WHERE
          tippable_type LIKE '%Answer%' AND
          tippable_id = ?
      ", [ $step->id ]);
  
      $t = 1;
      $m = count($tips);
      foreach ($tips as $tip)
      {
        $tname = $m == 1 ? $name.', tip' : $name.', '.$t.'e tip';
        $tip_id = Hashids::encode($tip->id);
        $parts[] = [
          'value' => 'tip#'.$tip_id,
          'label' => $tname,
          'info' => $tip->text,
          'tip_id' => $tip_id,
        ];
        $t++;
      }

      $s++;
    }

    return $parts;
  }

  public function parts($stream, $exam, $question)
  {
    $stream_id = Hashids::decode($stream)[0];
    $exam_id = Hashids::decode($exam)[0];
    $question_id = Hashids::decode($question)[0];

    $rows = DB::select("
      SELECT
        id
      FROM
        answers
      WHERE
        question_id = ?
      ORDER BY
        answers.position
      LIMIT 1
    ", [ $question_id ]);

    foreach ($rows as $row) {
      return $this->answer_parts($question_id, $row->id);
    }

    return [];
  }

  public function post(Request $request)
  {
    $data = $request->validate([
      'feedback' => 'required|string|max:255',
      'collection' => 'string|nullable',
      'stream' => 'string|nullable',
      'exam' => 'string|nullable',
      'question' => 'string|nullable',
      'part' => 'string|nullable',
    ]);

    $data['collection'] = NULL;
    $data['creator'] = NULL;
    if ($data['collection']) {
      $collection_id = Hashids::decode($data['collection'])[0];
      $rows = DB::select("
        SELECT
          users.email,
          collections.name AS collection
        FROM
          users, collections
        WHERE
          collections.user_id = users.id AND
          collections.id = ?
        LIMIT 1
      ", [ $collection_id ]);
      foreach ($rows as $row) {
        $data['collection'] = $row->collection;
        $data['creator'] = $row->email;
      }
    }

    if ($data['stream']) {
      $id = Hashids::decode($data['stream'])[0];
      $row = DB::select("
        SELECT slug FROM streams WHERE id = ?
      ", [ $id ])[0];
      $data['stream'] = $row->slug;
    }

    if ($data['exam']) {
      $id = Hashids::decode($data['exam'])[0];
      $row = DB::select("
        SELECT CONCAT(year,'-',term) AS slug FROM exams WHERE id = ?
      ", [ $id ])[0];
      $data['exam'] = $row->slug;
    }

    if ($data['question']) {
      $id = Hashids::decode($data['question'])[0];
      $row = DB::select("
        SELECT number, name
        FROM questions, topics
        WHERE questions.id = ? AND topic_id = topics.id
      ", [ $id ])[0];
      $data['question'] = $row->number;
      $data['topic'] = $row->name;
    } else {
      $data['topic'] = NULL;
    }

    $user = auth()->user();
    if ($user) {
      $data['email'] = $user->email;
      $data['first_name'] = $user->first_name;
      $data['last_name'] = $user->last_name;
    } else {
      $data['email'] = NULL;
      $data['first_name'] = NULL;
      $data['last_name'] = NULL;
    }

    $mail = new FeedbackMail($data);
    Mail::to('info@examenfit.nl')->send($mail);

    return $data;
  }
}
