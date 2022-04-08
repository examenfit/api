<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Vinkla\Hashids\Facades\Hashids;

class CustomQueries extends Controller
{
    const ACTIVITIES = "
      SELECT
        l.description AS Licentie,
        g.name AS Groep,
        u.email AS Leerling,
        a.created_at AS Tijdstip,
        a.activity AS Activiteit
      FROM
        licenses l,
        seats s,
        seat_group sg,
        `groups` g,
        users u,
        activity_logs a
      WHERE
        l.id IN (139,141,142) AND
        l.id = s.license_id AND
        s.user_id = u.id AND
        u.role = 'leerling' AND
        u.email = a.email AND
        sg.group_id = g.id AND
        sg.seat_id = s.id
      ORDER BY
        Licentie,
        Groep,
        Leerling,
        Tijdstip
    ";

    const QUESTION_COMPLEXITY_COUNT = "
      SELECT
        courses.name AS Vak,
        levels.name AS Niveau,
        CASE
          WHEN questions.complexity = 'low' THEN 'laag'
          WHEN questions.complexity = 'average' THEN 'gemiddeld'
          WHEN questions.complexity = 'high' THEN 'hoog'
        END AS Complexiteit,
        CASE
          WHEN questions.complexity = 'low' THEN 1
          WHEN questions.complexity = 'average' THEN 2
          WHEN questions.complexity = 'high' THEN 3
        END AS ComplexiteitScore,
        count(*) AS Vragen
      FROM
        courses,
        levels,
        streams,
        exams,
        topics,
        questions
      WHERE
        courses.id = course_id AND
        levels.id = level_id AND
        streams.id = stream_id AND
        exams.id = exam_id AND
        topics.id = topic_id
      GROUP BY
        Vak,
        Niveau,
        Complexiteit,
        ComplexiteitScore
      ORDER BY
        Vak,
        Niveau,
        ComplexiteitScore
    ";

    const QUESTION_COMPLEXITY_IS_NULL = "
      SELECT
        courses.name AS Vak,
        levels.name AS Niveau,
        year AS Jaar,
        term AS Tijdvak,
        topics.name AS Opgave,
        number AS Vraag
      FROM
        questions,
        topics,
        exams,
        streams,
        courses,
        levels
      WHERE
        topic_id=topics.id AND
        exam_id=exams.id AND
        stream_id=streams.id AND
        course_id = courses.id AND
        level_id = levels.id AND
        questions.complexity IS NULL
      ORDER BY
        Vak,
        Niveau,
        Jaar,
        Tijdvak,
        Vraag
    ";

    const QUESTIONS_NOT_IN_OEFENSETS = "
      SELECT
        courses.name as Vak,
        levels.name as Niveau,
        year as Jaar,
        term as Tijdvak,
        number as Vraag
      FROM
        questions,
        topics,
        exams,
        streams,
        levels,
        courses
      WHERE
        courses.id = course_id AND
        levels.id = level_id AND
        streams.id = stream_id AND
        exams.id = exam_id AND
        exams.status IS NOT NULL AND
        exams.status <> 'frozen' AND
        exams.show_answers AND
        topics.id = topic_id AND
        topics.has_answers AND
        questions.id NOT IN (
          SElECT
            question_id
          FROM
            question_annotation
        )
      ORDER BY
        Vak,
        Niveau,
        Jaar,
        Tijdvak,
        Vraag
    ";

    const QUESTIONS_WITH_MULTIPLE_ANSWERS = "
      SELECT
        courses.name AS Vak,
        levels.name AS Niveau,
        exams.year AS Jaar,
        exams.term AS Tijdvak,
        topics.name AS Opgave,
        questions.number AS Vraag,
        count(*) AS Aantal
      FROM
        courses,
        levels,
        streams,
        exams,
        topics,
        questions,
        answers
      WHERE
        course_id = courses.id AND
        level_id = levels.id AND
        stream_id = streams.id AND
        exam_id = exams.id AND
        topic_id = topics.id AND
        question_id = questions.id
      GROUP BY
        Vak,
        Niveau,
        Jaar, Tijdvak, Opgave,
        Vraag
      HAVING
        Aantal > 1
      ORDER BY
        Vak,
        Niveau,
        Jaar,
        Tijdvak,
        Vraag
    ";

    const LEERLINGLICENTIES_CSDEHOVEN = "
      SELECT
        'CS De Hoven' AS Registraties,
        COUNT(*) AS Aantal
      FROM
        registrations
      WHERE
        email LIKE '%csdehoven%'
      UNION SELECT
        'CS De Hoven; Geactiveerd',
        COUNT(*)
      FROM
        registrations
      WHERE
        email LIKE '%csdehoven%' AND
        activated IS NOT NULL
      UNION SELECT
        'CS De Hoven; Beschikbaar',
        COUNT(*)
      FROM
        registrations
      WHERE
        email LIKE '%csdehoven%' AND
        activated IS NULL
    ";

    public function activities()
    {
      return DB::select(CustomQueries::ACTIVITIES);
    }

    public function activities_tsv()
    {
      $type = 'text/tab-separated-values';
      $content = implode("\n", array_map(
        fn($row) =>
          $row->Licentie."\t".
          $row->Groep."\t".
          $row->Leerling."\t".
          $row->Tijdstip."\t".
          $row->Activiteit,
        DB::select(CustomQueries::ACTIVITIES)
      ));

      return response($content)
        ->header('Content-Type', $type);
    }

    public function questions_complexity_count()
    {
      return DB::select(CustomQueries::QUESTION_COMPLEXITY_COUNT);
    }

    public function questions_complexity_is_null()
    {
      return DB::select(CustomQueries::QUESTION_COMPLEXITY_IS_NULL);
    }

    public function questions_not_in_oefensets()
    {
      return DB::select(CustomQueries::QUESTIONS_NOT_IN_OEFENSETS);
    }

    public function questions_with_multiple_answers()
    {
      return DB::select(CustomQueries::QUESTIONS_WITH_MULTIPLE_ANSWERS);
    }

    public function leerlinglicenties_csdehoven()
    {
      return DB::select(CustomQueries::LEERLINGLICENTIES_CSDEHOVEN);
    }
}
