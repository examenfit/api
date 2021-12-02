<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Collection;
use App\Models\Privilege;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class ActivityLogController extends Controller
{
    public function index()
    {
        return response()->noContent(501);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'device_key' => 'string|required',
            'session_key' => 'string|required',
            'origin' => 'string|required',
            'activity' => 'string|required'
        ]);
        $data['collection_id'] = $request->collection_id ? Hashids::decode($request->collection_id)[0] : null;
        $data['topic_id'] = $request->topic_id ? Hashids::decode($request->topic_id)[0] : null;
        $data['question_id'] = $request->question_id ? Hashids::decode($request->question_id)[0] : null;
        $data['email'] = $user ? $user->email : null;
        ActivityLog::create($data);
        return response()->noContent(201);
    }


    public function collectionSummary(Collection $collection)
    {
        $counts = DB::select("
          SELECT
            topic_id,
            name,
            COUNT(DISTINCT device_key) AS devices,
            COUNT(*) AS logs
          FROM
            activity_logs,
            topics
          WHERE
            topic_id = topics.id AND
            collection_id = ?
          GROUP BY
            topic_id,
            name
        ", [ $collection->id ]);

        $topics = [];
        foreach ($counts as $count) {
          $topic = [];
          $topic['id'] = Hashids::encode($count->topic_id);
          $topic['name'] = $count->name;
          $topic['devices'] = $count->devices;
          $topic['logs'] = $count->logs;
          $topic['activities'] = DB::select("
            SELECT DISTINCT
              origin,
              activity,
              COUNT(DISTINCT device_key) AS devices,
              COUNT(*) AS logs
            FROM
              activity_logs
            WHERE
              topic_id = ? AND
              collection_id = ?
            GROUP BY
              origin, 
              activity
            ORDER BY
              origin,
              activity
          ", [ $count->topic_id, $collection->id ]);
          $topics[] = $topic;
        }

        return [
          'topics' => $topics
        ];
    }

    public function latestActivity(Privilege $privilege)
    {
        if ($privilege->action === 'oefensets uitvoeren' && $privilege->object_type === 'stream') {
            return $this->latestStreamActivity($privilege);
        } 
        if ($privilege->action === 'opgavenset uitvoeren' && $privilege->object_type === 'collection') {
            return $this->latestCollectionActivity($privilege);
        }
        return response()->noContent(422);
    }

    function latestStreamActivity(Privilege $privilege)
    {
        $stream_id = $privilege->object_id;
        $user_email = $privilege->seat->user->email;

        return DB::select("
          SELECT
            activity_logs.*
          FROM
            activity_logs,
            topics,
            exams
          WHERE
            activity IN ('Kijk antwoord na', 'Ontvang een tip') AND
            topic_id = topics.id AND
            exam_id = exams.id AND
            stream_id = ? AND
            question_id IS NOT NULL AND
            email = ?
          ORDER BY
            created_at DESC
          LIMIT 1
        ", [ $stream_id, $user_email ]);
    }

    public function latestCollectionActivity(Privilege $privilege)
    {
        $user_email = $privilege->seat->user->email;
        $collection_id = $privilege->object_id;

        return DB::select("
          SELECT
            activity_logs.*
          FROM
            activity_logs
          WHERE
            activity IN ('Kijk antwoord na', 'Ontvang een tip') AND
            question_id IS NOT NULL AND
            collection_id = ? AND
            email = ?
          ORDER BY
            created_at DESC
          LIMIT 1
        ", [ $collection_id, $user_email ]);
    }
}
