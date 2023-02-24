<?php

namespace App\Http\Controllers;

use DateTime;

use App\Http\Resources\ScoreResource;
use App\Models\Stream;
use App\Models\Privilege;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class ScoreController extends Controller
{
    const QUERY_SCORES = "
      SELECT
        s.*
      FROM
        scores s
      INNER JOIN (
        SELECT
          q.question_id,
          max(q.updatedAt) as maxUpdatedAt
        FROM
          scores q
        GROUP BY
          q.question_id
      ) r
      ON r.question_id = s.question_id AND
         r.maxUpdatedAt = s.updatedAt
      WHERE
        user_id = ?
    ";

    static function queryScores($user_id)
    {
        return DB::select(ScoreController::QUERY_SCORES, [ $user_id ]);
    }

    static function mapScores($rows)
    {
      $map = [];
      foreach($rows as $row) {
        $id = Hashids::encode($row->question_id);
        $map[$id] = [
          'updatedAt' => $row->updatedAt,
          'totalPoints' => $row->totalPoints,
          'scoredPoints' => $row->scoredPoints,
          'hasCompletedScoreFlow' => $row->hasCompletedScoreFlow,
          'sections' => json_decode($row->sections),
        ];
      }
      return $map;
    }

    static function getScores($user_id)
    {
        $rows = ScoreController::queryScores($user_id);
        return ScoreController::mapScores($rows);
    }

    // DEPRECATED
    public function loadAll(Request $request)
    {
        $user = auth()->user();
        $data = ScoreController::getScores($user->id);
        return response()->json($data);
    }

    // DEPRECATED
    public function saveAll(Request $request)
    {
        $user = auth()->user();
        $scores = $request->all();
        $r = [];
        $ignored = [];
        $warnings = false;
        $user_id = $user->id;
        $ts = new DateTime();
        foreach($scores as $question_id_hash => $score) {
          $question_id = Hashids::decode($question_id_hash)[0];
          if (array_key_exists('updatedAt', $score)) {
            DB::update("
              update
                scores
              set
                updated_at = ?
              where
                user_id = ?
               and 
                question_id = ?
            ", [ $ts, $user_id, $question_id ]);
            DB::insert("
              replace
              into
                scores
              set
                user_id = ?,
                question_id = ?,
                updatedAt = ?,
                totalPoints = ?,
                scoredPoints = ?,
                hasCompletedScoreFlow = ?,
                sections = ?,
                created_at = ?,
                updated_at = ?
            ", [
              $user_id,
              $question_id,
              $score['updatedAt'],
              $score['totalPoints'],
              $score['scoredPoints'],
              $score['hasCompletedScoreFlow'],
              json_encode($score['sections']),
              $ts,
              $ts
            ]);
          } else {
            $warnings = true;
            $ignored[] = $question_id;
          }
        }
        if ($warnings) {
            return response()->json([
                'status' => 'ok',
                'ignored' => $ignored
            ]);
        } else {
            return response()->json(['status' => 'ok']);
        }
    }

    // ======================================================================
    //
    // STREAM SCORES
    //
    // GET /api/streams/{stream}/scores
    // POST /api/streams/{stream}/scores {
    // }
    //
    // ======================================================================

    public function getStreamScores(Stream $stream)
    {
      $user = auth()->user();
      if (!$user) {
        return response()->noContent(401);
      }
      $user_id = $user->id;
      $stream_id = $stream->id;
      $rows = DB::select("
        SELECT
          *
        FROM
          scores
        WHERE
          user_id = ? AND
          stream_id = ? AND
          is_newest
      ", [ $user_id, $stream_id ]);
      $scores = ScoreController::mapScores($rows);
      return $scores;
    }

    public function getPrivilegeScores(Privilege $privilege)
    {
/*
      $user = auth()->user();
      if (!$user) {
        return response()->noContent(401);
      }
      if ($user->role !== 'docent') {
        return response()->noContent(403);
      }
*/
      if ($privilege->action !== 'oefensets uitvoeren') {
        return response()->noContent(403);
      }
      $user_id = $privilege->seat->user->id;
      $stream_id = $privilege->object_id;
      $rows = DB::select("
        SELECT
          *
        FROM
          scores
        WHERE
          user_id = ? AND
          stream_id = ? AND
          is_newest
      ", [ $user_id, $stream_id ]);
      $scores = ScoreController::mapScores($rows);
      return $scores;
    }

    public function postStreamScore(Stream $stream, Request $request)
    {
      $ts = new DateTime();
      $user_id = auth()->user()->id;
      $stream_id = $stream->id;
      $question_id = Hashids::decode($request->question_id)[0];

      DB::update("
        UPDATE
          scores
        SET
          is_newest = FALSE,
          stream_id = ?
        WHERE
          user_id = ? AND
          question_id = ?
      ", [ $stream_id, $user_id, $question_id ]);
      DB::insert("
        INSERT INTO
          scores
        SET
          user_id = ?,
          stream_id = ?,
          question_id = ?,
          updatedAt = ?,
          totalPoints = ?,
          scoredPoints = ?,
          hasCompletedScoreFlow = ?,
          sections = ?,
          created_at = ?,
          updated_at = ?,
          is_newest = TRUE
      ", [
          $user_id,
          $stream_id,
          $question_id,
          $request->updatedAt,
          $request->totalPoints ?: 0,
          $request->scoredPoints ?: 0,
          $request->hasCompletedScoreFlow ?: FALSE,
          json_encode($request->sections ?: []),
          $ts, $ts
      ]);

      return response()->noContent(201);
    }

    public function fix_totalPoints()
    {
      DB::update("
        update
          scores a,
          scores b
        set
          a.totalPoints = b.totalPoints
        where
          a.question_id = b.question_id and
          a.totalPoints < b.totalPoints and
          a.totalPoints = 0
      ");
      return 'hell-yeah!';
    }
}
