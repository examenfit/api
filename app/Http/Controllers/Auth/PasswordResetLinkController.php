<?php

namespace App\Http\Controllers\Auth;

use Exception;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;

use App\Models\PasswordReset;

class PasswordResetLinkController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        PasswordReset::sendToken($request->email);
        return response()->noContent(200);
    }
}
