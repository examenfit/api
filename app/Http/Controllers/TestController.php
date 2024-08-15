<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    public function show()
    {
        $migrations = DB::select("
          SELECT * FROM migrations
          ORDER BY id DESC LIMIT 1
        ");
        return [
          'status' => '2024-08-15.0001',
          'info' => config('test.info'),
          'last_migration' => $migrations[0],
        ];
    }
}
