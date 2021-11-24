<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Collection;

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
        return DB::select("
          SELECT DISTINCT
            origin,
            activity,
            COUNT(DISTINCT device_key) AS devices
          FROM
            activity_logs
          WHERE
            collection_id = ?
          GROUP BY
            origin, 
            activity
          ORDER BY
            origin,
            activity
        ", [ $collection->id ]);
    }
}
