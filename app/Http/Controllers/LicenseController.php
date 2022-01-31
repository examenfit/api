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
        'is_active' => $license->is_active,
        'description' => $license->description
      ], DB::select("
        select
          l.id,
          l.type,
          l.begin,
          l.end,
          l.is_active,
          l.description
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

    function createDemoLeerling(License $license)
    {
      $user = auth()->user();
      if (!$user) {
        return response()->noContent(401);
      }

      $demo = License::createDemoLeerling($license);

      //$mail = new InviteMail($demo, $user);
      //Mail::to($user->email)->send($mail);

      return new SeatResource($demo);
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
        'groups',
        'seats.groups',
        'seats.privileges',
        'seats.user',
      ]);
      return new LicenseResource($license);
    }

    public function put(License $license, Request $request)
    {
      $data = $request->validate([
        'type' => 'string|required',
        'begin' => 'date|required',
        'end' => 'date|required',
        'is_active' => 'integer|nullable',
        'description' => 'string|nullable'
      ]);

      if ($data['type'] !== 'proeflicentie' && $data['type'] !== $license['type']) {
        foreach($license->seats as $seat) {
          foreach($seat->privileges as $privilege) {
            if (str_starts_with($privilege->action, 'beperkt ')) {
              $privilege->action = substr($privilege->action, 8);
              $privilege->save();
            }
          }
        }
      }

      if ($data['end'] !== $license['end']) {
        foreach($license->seats as $seat) {
          foreach($seat->privileges as $privilege) {
            $privilege->end = $data['end'];
            $privilege->save();
          }
        }
      }

      $license->fill($data);
      $license->save();
      return new LicenseResource($license);
    }

    public function delete(License $license)
    {
      return response()->noContent(501);
    }

    public function postSeat(License $license, Request $request)
    {
      return response()->noContent(501);
    }

    function getDocent($license)
    {
      foreach($license->seats as $seat) {
        if ($seat->role === 'docent') {
          return $seat;
        }
      }
      return NULL;
    }

    function createLeerling($data)
    {
      $license_id = Hashids::decode($data['license_id'])[0];
      $init = [
        'license_id' => $license_id,
        'role' => 'leerling',
        'token' => Str::random(32),
      ];

      if (isset($data['email'])) $init['email'] = $data['email'];
      if (isset($data['first_name'])) $init['first_name'] = $data['first_name'];
      if (isset($data['last_name'])) $init['last_name'] = $data['last_name'];

      $seat = Seat::create($init);

      $license = $seat->license;

      $group = Group::firstWhere('name', $data['group']);
      if (!$group) {
        $group = Group::create([
          'license_id' => $license->id,
          'name' => $data['group'],
          'is_active' => TRUE,
        ]);
      }
      $seat->groups()->sync([ $group->id ]);

      foreach ($data['streams'] as $stream_hash_id) {
        $stream_id = Hashids::decode($stream_hash_id)[0];
        Privilege::create([
          'actor_seat_id' => $seat->id,
          'action' => 'oefensets uitvoeren',
          'object_type' => 'stream',
          'object_id' => $stream_id,
          'begin' => $license->begin,
          'end' => $license->end,
        ]);
      }

      return $seat;
    }

    function inviteLeerling($seat) {
      if ($seat->email) {
        $docent = $this->getDocent($seat->license);
        if ($docent) {
          $mail = new InviteMail($seat, $docent->user);
          Mail::to($seat->email)->send($mail);
        }
      }
    }

    public function postLeerlingen(Request $request)
    {
      $seats = [];
      foreach($request->seats as $data) {
        $seats[] = $this->createLeerling($data);
      }
      foreach($seats as $seat) {
        $this->inviteLeerling($seat);
      }
      return SeatResource::collection($seats);
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
      $id = $seat->hash_id;
      $seat->groups()->sync([]);
      $seat->privileges()->delete();
      $seat->delete();

      return response()->json([
        'status' => 'ok',
        'message' => 'deleted seat #'.$id
      ]);
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
        $user = auth()->user();
        $user_id = $user->id;
        $groups = Group::whereHas(
          'seats', fn($q) => $q->where('user_id', $user_id)
        );
        return GroupResource::collection($groups->get());
    }

    public function getOwnedGroups()
    {
        $user = auth()->user();
        $user->load(['seats.privileges' => fn($q) => $q->where('action', 'groepen beheren')]);
        $groups = [];
        foreach ($user->seats as $seat) {
            foreach ($seat->privileges as $priv) {
                $group = Group::find($priv->object_id);
                $group->load('license');
                $groups[] = $group;
            }
        }
        return GroupResource::collection($groups);
    }

    public function getGroup(Group $group)
    {
        $user = auth()->user();
        if (Privilege::granted('groep beheren', $group)) {
          $group->load('seats.user');
          return new GroupResource($group);
        }
        return response()->noContent(403);
    }

    private function grantToSeat($seat, $action, $object_type, $object_id)
    {
        Privilege::create([
            'actor_seat_id' => $seat->id,
            'action' => $action,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'begin' => $seat->license->begin,
            'end' => $seat->license->end,
            'is_active' => 1
        ]);
    }

    private function grantToGroupSeats($group, $action, $object_type, $object_id)
    {
        $seats = $group->seats();
        foreach ($seats as $seat) {
            $this->grantToSeat($seat, $action, $object_type, $object_id);
        }
    }

    public function postPrivilegeToGroup(Group $group)
    {
        $user = auth()->user();
        if (!Privilege::granted('groep beheren', $group)) {
          return response()->noContent(403);
        }
        $this->grantToGroupSeats(
            $group,
            $request->action,
            $request->object_type,
            $request->object_id
        );
    }

    public function putGroup()
    {
        return reponse()->noContent(501);
    }
}
