<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function show()
    {
        return json_decode(auth()->user()->data);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $user->data = json_encode($request->all());
        $user->save();

        return response()->json(['message' => 'ok'], 200);
    }
}
