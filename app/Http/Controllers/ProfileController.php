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

    public function store_userprofile(Request $request)
    {
        $user = auth()->user();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->newsletter = $request->newsletter;
        $user->data = json_encode($request->data);
        $user->save();

        return response()->json(['message' => 'ok'], 200);
    }
}
