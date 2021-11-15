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

      Auth::login($login);
      return new UserResource($login);
    }
}
