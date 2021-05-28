<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Models\User;

use Illuminate\Http\Request;

class NewPasswordController extends Controller
{
    public function save(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string',
        ]);

        PasswordReset::install($request->token, $request->password);
        return response()->json([ 'message' => 'password saved' ]);
    }
}
