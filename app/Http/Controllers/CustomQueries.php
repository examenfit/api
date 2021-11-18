<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Vinkla\Hashids\Facades\Hashids;

class CustomQueries extends Controller
{
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
        Jaar,
        Tijdvak,
        Opgave,
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

    public function questions_with_multiple_answers()
    {
      return DB::select(CustomQueries::QUESTIONS_WITH_MULTIPLE_ANSWERS);
    }

    public function questions_complexity_is_null()
    {
      return DB::select(CustomQueries::QUESTION_COMPLEXITY_IS_NULL);
    }
}