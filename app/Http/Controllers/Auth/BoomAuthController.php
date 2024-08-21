<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\BoomAuthRequest;
use App\Http\Resources\UserResource;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BoomAuthController extends Controller
{
  private function isAuthorized($user)
  {
    $hasValidRole =
      $user->role === "leerling" ||
      $user->role === "docent" ||
      $user->role === "author" ||
      $user->role === "admin";

    return $hasValidRole;
  }

  public function store(BoomAuthRequest $request)
  {
    $request->authenticate();

    $user = Auth::User();

    if ($user && $this->isAuthorized($user)) {
      $user->load([
        'seats.groups',
        'seats.license',
        'seats.privileges',
      ]);
      $request->session()->regenerate();
      return new UserResource($user);
    }

    return response()->json([
      'status' => 'unauthorized',
      'message' => 'The given data was invalid.',
      'errors' => ['token' => ['Failed to resolve user.']]
    ], 422);
  }

  public function fix_marcel(Request $request) {
    DB::update("
      UPDATE users SET email = id WHERE email LIKE 'marcel+%'
    ");
    DB::update("
      UPDATE licenses SET brin_id = null, type = 'deleted' WHERE brin_id LIKE 'EX%'
    ");
    return [
      'status' => 'ok',
      'detached' => [
        'users' => "email=marcel+%",
        'licenses' => "brin_id=EX%",
      ],
    ];
  }
}
