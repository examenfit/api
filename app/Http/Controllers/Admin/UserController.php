<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Vinkla\Hashids\Facades\Hashids;

class UserController extends Controller
{
    public function index(Request $request)
    { 
        if ($request->email) {
          $user = User::firstWhere('email', $request->email);
          if (!$user) {
            return response()->json(null);
          }
          $user->load([
            'seats.license',
            'seats.privileges'
          ]);
          return new UserResource($user);
        }
        return UserResource::collection(User::all());
    }

    public function log(Request $request)
    { 
        $request->validate([
          'email' => 'required|email',
          'count' => 'nullable|integer',
        ]);

        return DB::select("
          SELECT
            activity,
            created_at AS ts
          FROM
            activity_logs
          WHERE
            email = ?
          ORDER BY
            2 DESC
          LIMIT ?
        ", [
          $request->email,
          $request->count ?: 10
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,author,participant,docent,leerling',
            'password' => 'required|min:8',
        ]);

        User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => bcrypt($data['password']),
        ]);

        return response(null, 201);
    }
}
