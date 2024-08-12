<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Group;
use App\Models\Privilege;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

class ProfileController extends Controller
{
    public function show()
    {
        return json_decode(auth()->user()->data);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $props = $request->validate([
            'use_groups' => 'array|nullable',
            'manage_groups' => 'array|nullable',
        ]);

        $seat = NULL;
        foreach($user->seats as $x) {
          $license = $x->license;
          if ($license->is_active && $license->brin_id) {
            $seat = $x;
          }
        }

        if (array_key_exists('use_groups', $props)) {
          $groupIds = [];
          $groupHashids = $props['use_groups'];
          foreach($groupHashids as $hashid) {
            $groupId = Hashids::decode($hashid)[0];
            $groupIds[] = $groupId;
          }
          $seat->groups()->sync($groupIds);
        }

        if (array_key_exists('manage_groups', $props)) {
          $groupNames = $props['manage_groups'];
          $license = $seat->license;
          foreach($groupNames as $groupName) {
            $group = Group::firstWhere([
                'license_id' => $license->id,
                'name' => $groupName,
            ]);
            $privilege = Privilege::firstOrCreate([
              'action' => 'groepen beheren',
              'actor_seat_id' => $seat->id,
              'object_id' => $group->id,
              'object_type' => 'group',
            ], [
              'begin' => new DateTime(),
              'end' => $license->end,
            ]);
          }
        }

        $user->data = json_encode($request->all());
        $user->save();

        return response()->json(['message' => 'ok'], 200);
    }

    public function store_userprofile(Request $request)
    {
        $user = auth()->user();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->newsletter = $request->newsletter;
        $user->data = json_encode($request->data);
        $user->save();

        return response()->json(['message' => 'ok'], 200);
    }
}
