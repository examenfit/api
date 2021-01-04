<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\TopicController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\AnswerController;
use App\Http\Controllers\Admin\AttachmentController;
use App\Http\Controllers\Admin\TeacherDocumentController;
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

Route::get('/latex-test', function () {
    return \App\Models\Question::first();
});

Route::group(['middleware' => 'auth:sanctum'], function() {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::get('/user', [AuthenticatedSessionController::class, 'show']);

    Route::group(['prefix' => 'admin', 'middleware' => 'role:admin'], function() {
        Route::get('/courses', [CourseController::class, 'index']);
        Route::get('/courses/{course}', [CourseController::class, 'show']);
        Route::get('courses/{course}/meta', [CourseController::class, 'showMeta']);

        Route::get('/exams', [ExamController::class, 'index']);
        Route::post('/exams', [ExamController::class, 'store']);
        Route::get('/exams/{exam}', [ExamController::class, 'show']);
        Route::put('/exams/{exam}', [ExamController::class, 'update']);
        Route::delete('/exams/{exam}', [ExamController::class, 'destroy']);

        Route::post('/exams/{exam}/topics', [TopicController::class, 'store']);

        Route::get('/topics/{topic}', [TopicController::class, 'show']);
        Route::delete('/topics/{topic}', [TopicController::class, 'destroy']);
        Route::put('/topics/{topic}', [TopicController::class, 'update']);

        Route::post('topics/{topic}/questions', [QuestionController::class, 'store']);

        Route::get('/questions/{question}', [QuestionController::class, 'show']);
        Route::put('/questions/{question}', [QuestionController::class, 'update']);
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy']);

        Route::get('/answers/{answer}', [AnswerController::class, 'show']);
        Route::post('/questions/{question}/answers', [AnswerController::class, 'store']);
        Route::put('/answers/{answer}', [AnswerController::class, 'update']);

        Route::get('attachments',  [AttachmentController::class, 'index']);
        Route::post('attachments', [AttachmentController::class, 'store']);

        Route::get('/teacher-document/{exam}', [TeacherDocumentController::class, 'index']);
    });
});
