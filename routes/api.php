<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Admin\TipController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\Admin\AnswerController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\AttachmentController;
use App\Http\Controllers\Admin\TeacherDocumentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\TopicController as AdminTopicController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\IndexController as AdminIndexController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AuditController as AdminAuditController;

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

Route::get('/collections/{collection}/{topic?}', [CollectionController::class, 'show']);
Route::post('/collections/{collection}/{question}/elaborations', [CollectionController::class, 'storeElaboration']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::get('/user', [AuthenticatedSessionController::class, 'show']);

    Route::get('/topics/{topic}', [TopicController::class, 'show']);

    Route::get('/courses/{course}/search/', [SearchController::class, 'index']);
    Route::get('/courses/{course}/tags/', [CourseController::class, 'showTags']);
    Route::get('/courses/{course}/tags/{tag}', [CourseController::class, 'showTag']);
    Route::get('/courses/{course}/search/results', [SearchController::class, 'results']);

    Route::get('/cart', [CartController::class, 'index']);

    Route::post('/collections', [CollectionController::class, 'store']);

    Route::group(['prefix' => 'admin', 'middleware' => 'role:admin,author'], function () {
        Route::get('/', [AdminIndexController::class, 'index']);

        Route::get('/courses', [AdminCourseController::class, 'index']);
        Route::get('/courses/{course}', [AdminCourseController::class, 'show']);
        Route::get('courses/{course}/meta', [AdminCourseController::class, 'showMeta']);

        Route::get('/courses/{course}/exams', [ExamController::class, 'index']);
        Route::post('/exams', [ExamController::class, 'store']);
        Route::get('/exams/{exam}', [ExamController::class, 'show']);
        Route::put('/exams/{exam}', [ExamController::class, 'update']);
        Route::delete('/exams/{exam}', [ExamController::class, 'destroy']);

        Route::post('/exams/{exam}/topics', [AdminTopicController::class, 'store']);

        Route::get('/topics/{topic}', [AdminTopicController::class, 'show']);
        Route::delete('/topics/{topic}', [AdminTopicController::class, 'destroy']);
        Route::put('/topics/{topic}', [AdminTopicController::class, 'update']);

        Route::post('topics/{topic}/questions', [QuestionController::class, 'store']);

        Route::get('/questions/{question}', [QuestionController::class, 'show']);
        Route::put('/questions/{question}', [QuestionController::class, 'update']);
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy']);

        Route::get('/answers/{answer}', [AnswerController::class, 'show']);
        Route::post('/questions/{question}/answers', [AnswerController::class, 'store']);
        Route::put('/answers/{answer}', [AnswerController::class, 'update']);
        Route::put('/answers/{answer}/sections/{answerSection}', [AnswerController::class, 'updateSection']);

        Route::put('/tips', [TipController::class, 'update']);

        Route::get('attachments',  [AttachmentController::class, 'index']);
        Route::post('attachments', [AttachmentController::class, 'store']);

        Route::get('/teacher-document/{exam}', [TeacherDocumentController::class, 'index']);
    });

    Route::group(['prefix' => 'admin', 'middleware' => 'role:admin'], function () {
        Route::get('/audit', [AdminAuditController::class, 'index']);

        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
    });
});
