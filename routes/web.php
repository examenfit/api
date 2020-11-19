<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    dump('test');
});

Route::get('/storage/{path}', function ($file) {
    $path = storage_path('app/public/'.$file);

    return response(file_get_contents($path), 200)
            ->header('Content-Type', mime_content_type($path));
})->where('path', '(.*)');
