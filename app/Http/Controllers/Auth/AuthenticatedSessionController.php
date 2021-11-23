<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Auth\LoginRequest;

class AuthenticatedSessionController extends Controller
{
    private function isAuthorized($user)
    {
      //return true;

      $hasValidRole =
        $user->role === "leerling" ||
        $user->role === "docent" ||
        $user->role === "author" ||
        $user->role === "admin";

      return $hasValidRole;
    }

    public function show()
    {
        $user = Auth::User();
        $user->load('seats.license');

        if ($user && $this->isAuthorized($user)) {
          return new UserResource($user);
        }

        if ($user) {
          $email = $user->email;
          $role = $user->role;
          Auth::guard('web')->logout();
          return response()->json([
            'status' => 'unauthorized',
            'message' => 'invalid role',
            'user' => [
              'email' => $email,
              'role' => $role
            ]
          ], 401);
        }

        return response()->noContent(401);
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $user = Auth::User();

        if ($this->isAuthorized($user)) {
          $request->session()->regenerate();
          return new UserResource($user);
        }

        return response()->json([
          'status' => 'unauthorized',
          'message' => 'The given data was invalid.',
          'errors' => ['email' => ['These credentials are not authorized.']]
        ], 422);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return ['result' => true];
    }
}
