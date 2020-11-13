<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\IncomingExamsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest');

Route::group(['middleware' => 'auth:sanctum'], function() {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::get('/user', [AuthenticatedSessionController::class, 'show']);

    Route::group(['prefix' => 'admin', 'middleware' => 'role:admin'], function() {
        Route::get('/incoming-exams', [IncomingExamsController::class, 'index']);
        Route::get('/incoming-exams/{incomingExam}', [IncomingExamsController::class, 'show']);
        Route::post('/incoming-exams', [IncomingExamsController::class, 'store']);

        Route::get('/exams', [Admin\ExamsController::class, 'index']);
    });
});
