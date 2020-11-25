<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\TopicController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\AttachmentController;
use App\Http\Controllers\Admin\IncomingExamsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

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

        Route::get('/courses', [CourseController::class, 'index']);
        Route::get('/courses/{course}', [CourseController::class, 'show']);
        Route::get('courses/{course}/facets', [CourseController::class, 'showFacets']);

        Route::get('/exams', [ExamController::class, 'index']);
        Route::post('/exams', [ExamController::class, 'store']);
        Route::post('/exams/{exam}/topics', [TopicController::class, 'store']);

        Route::put('/questions/{question}', [QuestionController::class, 'update']);
        Route::put('/topics/{topic}', [TopicController::class, 'update']);

        Route::post('topics/{topic}/questions', [QuestionController::class, 'store']);


        Route::post('attachments', [AttachmentController::class, 'store']);
    });
});
