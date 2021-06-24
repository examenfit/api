<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Topic;
use App\Models\Level;
use Illuminate\Http\Request;
use App\Http\Resources\LevelResource;
use Vinkla\Hashids\Facades\Hashids;

class LevelController extends Controller
{
    public function index()
    {
        return LevelResource::collection(Level::all());
    }
}
