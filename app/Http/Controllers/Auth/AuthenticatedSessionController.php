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

        if ($user && $this->isAuthorized($user)) {
          return new UserResource($user);
        }

        Auth::guard('web')->logout();

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

        $request->session()->regenerate();

        return new UserResource(Auth::user());
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
