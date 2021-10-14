<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ScoreResource;
use Vinkla\Hashids\Facades\Hashids;

class ScoreController extends Controller
{
    public function loadAll(Request $request)
    {
        $user = auth()->user();
        $r = DB::select("
          select
            s.*
          from
            scores s
          inner join (
            select
              q.question_id,
              max(q.updatedAt) as maxUpdatedAt
            from
              scores q
            group by
              q.question_id
          ) r
          on
            r.question_id = s.question_id and r.maxUpdatedAt = s.updatedAt
          where
            user_id = ?
        ", [ $user->id ]);
        if (count($r)) {
          $score = [];
          foreach($r as $rec) {
            $id = Hashids::encode($rec->question_id);
            $score[$id] = [
              'updatedAt' => $rec->updatedAt,
              'totalPoints' => $rec->totalPoints,
              'scoredPoints' => $rec->scoredPoints,
              'hasCompletedScoreFlow' => $rec->hasCompletedScoreFlow,
              'sections' => json_decode($rec->sections),
            ];
          }
          return $score;
        } else {
          return response()->json([]);
        }
    }

    public function saveAll(Request $request)
    {
        $user = auth()->user();
        $scores = $request->all();
        $r = [];
        $ignored = [];
        $warnings = false;
        foreach($scores as $question_id => $score) {
          if (array_key_exists('updatedAt', $score)) {
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
                sections = ?
            ", [
              $user->id,
              Hashids::decode($question_id)[0],
              $score['updatedAt'],
              $score['totalPoints'],
              $score['scoredPoints'],
              $score['hasCompletedScoreFlow'],
              json_encode($score['sections']),
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
}