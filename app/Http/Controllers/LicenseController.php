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
    public function index()
    {
      $user = auth()->user();
      if (!$user) {
        return response()->noContent(401);
      }
      if ($user->role === 'admin') {
        $licenses = License::all();
        $licenses->load(['owner']);
        return LicenseResource::collection($licenses);
      }
      return response()->noContent(403);
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

    public function putSeat(License $license, Seat $seat)
    {
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
