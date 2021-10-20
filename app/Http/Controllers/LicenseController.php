<?php

namespace App\Http\Controllers;

use DateTime;
use DateTimeZone;

use App\Models\License;
use App\Models\Seat;
use App\Models\Privilege;

use App\Http\Resources\LicenseResource;
use App\Http\Resources\SeatResource;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class LicenseController extends Controller
{
    // /api/licenses
    public function index()
    {
      $user = auth()->user();
      if (!$user) {
        return response()->noContent(401);
      }
      if ($user->role === 'admin') {
        $licenses = License::all(); // fixme
        return LicenseResource::collection($licenses);
      }
      return array_map(fn ($license) => [
        'id' => Hashids::encode($license->id),
        'type' => $license->type,
        'end' => $license->end
      ], DB::select("
        select
          l.id,
          l.type,
          l.begin,
          l.end,
          l.is_active
        from
          licenses l,
          seats s
        where
          s.license_id = l.id
         and
          s.user_id = ?
      ", [ $user->id ]));

      //return response()->noContent(403);
    }

    public function post(Request $request)
    {
      $data = $request->validate([
        'type' => 'required|string'
      ]);

      switch ($data['type']) {
        case 'proeflicentie':
          return createProeflicentie($request);
        default:
          die('unknown type');
      }
    }

    public function get(License $license)
    {
      $license->load([
        'seats.privileges',
        'seats.user',
      ]);
      return new LicenseResource($license);
    }

    public function put(License $license)
    {
      return response()->noContent(501);
    }

    public function delete(License $license)
    {
      return response()->noContent(501);
    }

    public function postSeat(License $license, Request $request)
    {
      return response()->noContent(501);
    }

    public function getSeat(License $license, Seat $seat)
    {
      $seat->load([
        'user',
        'privileges'
      ]);
      return new SeatResource($seat);
    }

    public function putSeat(License $license, Seat $seat, Request $request)
    {
      if ($seat->license_id !== $license->id) {
        return response()->noContent(400);
      }

      $data = $request->validate([
        'email' => 'required|email',
        'first_name' => 'required|string',
        'last_name' => 'required|string',
        'is_active' => 'boolean'
      ]);
      $seat->email = $data['email'];
      $seat->first_name = $data['first_name'];
      $seat->last_name = $data['last_name'];
      $seat->is_active = $data['is_active'];
      $seat->save();

      return new SeatResource($seat);

      // $seat->license_id === $license->id
      // $seat->email
      // $seat->first_name
      // $seat->last_name
      // $seat->is_active
      // $seat->begin
      // $seat->end
      return response()->noContent(501);
    }

    public function invite(License $license, Seat $seat)
    {
      // $seat->license_id === $license->id
      // $seat->email
      // $seat->first_name
      // $seat->last_name
      return response()->noContent(501);
    }

    public function deleteSeat(License $license, Seat $seat)
    {
      return response()->noContent(501);
    }

    public function postPrivilege(License $license, Seat $seat, Request $request)
    {
      return response()->noContent(501);
    }

    public function getPrivilege(License $license, Seat $seat, Privilege $privilege)
    {
      return response()->json([
        'privilege' => null
      ], 501);
    }

    public function putPrivilege(License $license, Seat $seat, Privilege $privilege)
    {
      return response()->noContent(501);
    }

    public function deletePrivilege(License $license, Seat $seat, Privilege $privilege)
    {
      return response()->noContent(501);
    }
}
