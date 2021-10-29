<?php

namespace App\Http\Controllers;

use DateTime;
use DateTimeZone;
use Mail;

use App\Models\User;
use App\Models\License;
use App\Models\Seat;
use App\Models\Privilege;
use App\Models\Group;

use App\Http\Resources\GroupResource;
use App\Http\Resources\LicenseResource;
use App\Http\Resources\SeatResource;
use App\Mail\InviteMail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        'begin' => $license->begin,
        'end' => $license->end,
        'is_active' => $license->is_active
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

    function createProeflicentie($request)
    {
      //$user_id = $request->user_id;
      //$stream_ids = explode(',', $request->stream_ids)

      return response()->noContent(501);
    }

    public function post(Request $request)
    {
      $data = $request->validate([
        'type' => 'required|string'
      ]);

      switch ($data['type']) {
        case 'proeflicentie':
          return $this->createProeflicentie($request);
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

    function validateInvite($token)
    {
        $seat = Seat::firstWhere('token', $token);
        if (!$seat) {
          return 'unknown-seat';
        }

        if ($seat->user_id) { 
          return 'invalid-seat';
        }

        $user = User::firstWhere('email', $seat->email);
        if ($user) {
          return 'valid-email';
        }

        else {
          return 'unknown-email';
        }
    }

    public function getInviteStatus(Request $request)
    {
        $request->validate([
          'token' => 'string:required'
        ]);

        $token = $request->token;
        $status = $this->validateInvite($request->token);

        return response()->json([ 'status' => $status ]);
    }


    function createSeatUser($seat, $password)
    {
        $now = new DateTime();
        $user = User::create([
            'first_name' => $seat->first_name,
            'last_name' => $seat->last_name,
            'email' => $seat->email,
            'password' => bcrypt($password),
            'email_verified_at' => $now, // fixme
            'role' => $seat->role
        ]);
        $seat->user_id = $user->id;
        $seat->save();
    }

    public function postInviteAccount(Request $request)
    {
        $token = $request->token;

        $status = $this->validateInvite($token);
        if ($status !== 'unknown-email') {
            return response()->json(['message' => 'invalid state'], 400);
        }

        $password = $request->password;
        if (!$password) {
            return response()->json(['message' => 'password required'], 400);
        }

        $seat = Seat::firstWhere('token', $token);
        $this->createSeatUser($seat, $password);

        return response()->json([
          'status' => 'ok'
        ]);
    }

    function assignUser($seat)
    {
        $user = User::firstWhere('email', $seat->email);
        $seat->user_id = $user->id;
        $seat->save();
    }

    public function postInviteOk(Request $request)
    {
        $token = $request->token;

        $status = $this->validateInvite($token);
        if ($status !== 'valid-email') {
            return response()->json(['message' => 'invalid state'], 400);
        }

        $seat = Seat::firstWhere('token', $token);
        $this->assignUser($seat);

        return response()->json([
          'status' => 'ok'
        ]);
    }

    private function sendInviteMail($seat)
    {
        $user = auth()->user();
        $mail = new InviteMail($seat, $user);
        Mail::to($seat->email)->send($mail);
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
      $seat->token = Str::random(32);
      $seat->save();

      $this->sendInviteMail($seat);

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

    public function getGroups()
    {
        $user_id = auth()->user()->id;
        $groups = DB::select("
          select g.*
          from
            `groups` g,
            `seat_group` r,
            `seats` s
          where 
             g.id = r.group_id and
             s.id = r.seat_id and
             s.user_id = ?
        ", [ $user_id ]);
        return GroupResource::collection($groups);
    }

    public function getGroup(Group $group)
    {
        $user = auth()->user();
        $seat = $user ?: Seat::firstWhere('user_id', $user->id);
        //$priv = $seat ?: 
        if ($user->role !== 'leerling') {
          $group->load('seats');
        }
        return new GroupResource($group);
    }

    public function putGroup()
    {
        return reponse()->noContent(501);
    }
}
