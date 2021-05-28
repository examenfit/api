<?php

namespace App\Models;

use App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;

class PasswordReset extends Model
{
    use HasFactory;
    public $timestamps = false;

    public $fillable = [
        'email',
        'token'
    ];

    public static function sendToken($email) {
        $user = User::where('email', $email)->first();
        if ($user) {
            $token = Str::random(32);
            PasswordReset::where('email', $email)->delete();
            PasswordReset::create([
                'email' => $email,
                'token' => $token
            ])->save();
            $user->sendPasswordResetNotification($token);
        }
    }

    public static function install($token, $password) {
        $ticket = PasswordReset::where('token', $token)->first();
        if ($ticket) {
            $user = User::where('email', $ticket->email)->first();
            $user->password = bcrypt($password);
            $user->save();
            PasswordReset::where('token', $token)->delete();
            return 'ok';
        } else {
            return 'invalid token';
        }
    }
}
