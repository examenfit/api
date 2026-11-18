<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserSwitchController extends Controller
{
    public function getUsers()
    {
      $user = Auth::user();
      $users = User::query()
        ->whereNotNull('link')
        ->where('link', $user->link ?: '!@#$%^&*(')
        ->get();

      return UserResource::collection($users);
    }

    public function switchToUser(Request $request)
    {
      $user = Auth::user();

      $login = User::query()
        ->whereNotNull('link')
        ->where('link', $user->link)
        ->where('email', $request->email)
        ->first();

      // the route runs under auth:sanctum, which makes 'sanctum' the default guard for the request;
      // the switch must log in on the session guard, so name it explicitly (RequestGuard has no login())
      Auth::guard('web')->login($login);

      $login->load([
        'seats.groups',
        'seats.license',
        'seats.privileges',
      ]);

      return new UserResource($login);
    }
}
