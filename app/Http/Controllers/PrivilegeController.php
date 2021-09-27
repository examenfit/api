<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Seat;
use App\Models\Privilege;

use App\Http\Resources\LicenseResource;
use App\Http\Resources\SeatResource;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class PrivilegeController extends Controller
{
    public function privileges()
    {
      $user = auth()->user();

      $results = DB::select("
        select
          distinct action
        from
          licenses,
          privileges,
          seats
        where
          licenses.id = seats.license_id and
          licenses.is_active and
          seats.id = privileges.actor_seat_id and
          seats.user_id = ? and
          seats.is_active and
          privileges.is_active and
          now() between privileges.begin and privileges.end
      ", [ $user->id ]);

      return collect($results)->map(fn($rec) => $rec->action);
    }

    public function privilege(Request $request)
    {
      $user = auth()->user();

      $action = $request->input('action');

      if (!$action) {
        return response()->json([
          'status' => 'action field required'
        ], 400);
      }

      return DB::select("
        select
          action,
          object_id,
          object_type,
          privileges.end
        from
          licenses,
          privileges,
          seats
        where
          licenses.id = seats.license_id and
          licenses.is_active and
          seats.id = privileges.actor_seat_id and
          seats.user_id = ? and
          seats.is_active and
          privileges.action = ? and
          privileges.is_active and
          now() between privileges.begin and privileges.end
      ", [ $user->id, $action ]);
    }

    public function objects(Request $request)
    {
      $user = auth()->user();

      $object_type = $request->input('object_type');

      if (!$object_type) {
        return response()->json([
          'status' => 'object_type field required'
        ], 400);
      }

      $objects = DB::select("
        select
          action,
          object_id,
          object_type,
          privileges.end
        from
          licenses,
          privileges,
          seats
        where
          licenses.id = seats.license_id and
          licenses.is_active and
          seats.id = privileges.actor_seat_id and
          seats.user_id = ? and
          seats.is_active and
          privileges.object_type = ? and
          privileges.is_active and
          now() between privileges.begin and privileges.end
      ", [ $user->id, $object_type ]);

      return array_map(fn($row) => [
        'action' => $row->action,
        'object_id' => Hashids::encode($row->object_id),
        'object_type' => $row->object_type,
        'end' => $row->end,
      ], $objects);
    }
}
