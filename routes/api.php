<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\PrivilegeController;
use App\Http\Controllers\AnnotationController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\Admin\TipController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\Admin\AnswerController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\AttachmentController;
use App\Http\Controllers\Admin\TeacherDocumentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\TopicController as AdminTopicController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\IndexController as AdminIndexController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\ChapterController as AdminChapterController;

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

// fixme these routes should probs not be public

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest');

Route::get('/topics/{topic}', [TopicController::class, 'show']);

Route::get('/annotations/{stream}', [AnnotationController::class, 'index']);
Route::get('/annotations/{stream}/types', [AnnotationController::class, 'types']);
Route::get('/annotations/{stream}/types/{type}', [AnnotationController::class, 'type']);
Route::get('/annotations/{stream}/oefensets', [AnnotationController::class, 'oefensets']);
Route::get('/annotations/{stream}/{annotation}', [AnnotationController::class, 'get']);

Route::get('/download-collection/{collection}', [CollectionController::class, 'showCollectionQuestionsDocument']);
Route::get('/download-collection-html/{collection}', [CollectionController::class, 'showCollectionQuestionsHtml']);
Route::get('/download-collection-pdf/{collection}', [CollectionController::class, 'showCollectionQuestionsPdf']);

Route::get('/download-appendixes-html/{topic}', [TopicController::class, 'html']);
Route::get('/download-appendixes-pdf/{topic}', [TopicController::class, 'pdf']);

/*
Route::get('/invalid_domains/', [StreamController::class, 'invalid_domains']);
Route::get('/fixable_domains/', [StreamController::class, 'fixable_domains']);
Route::get('/fix_domains/', [StreamController::class, 'fix_domains']);
Route::get('/invalid_tags/', [StreamController::class, 'invalid_tags']);
Route::get('/fixable_tags/', [StreamController::class, 'fixable_tags']);
Route::get('/fix_tags/', [StreamController::class, 'fix_tags']);

Route::get('/unknown_usage', [CollectionController::class, 'unknown_usage']);
Route::get('/fix_usage', [CollectionController::class, 'fix_usage']);

Route::get('/null_stream_chapters/', [StreamController::class, 'null_stream_chapters']);
Route::get('/fix_null_stream_chapters/', [StreamController::class, 'fix_null_stream_chapters']);

Route::get('/0step_answer_sections/', [StreamController::class, 'Ostep_answer_sections']);
Route::get('/fix_answer_sections/', [StreamController::class, 'fix_answer_sections']);
*/

Route::get('/collections/{collection}/{topic?}', [CollectionController::class, 'show']);
Route::post('/collections/{collection}/{question}/elaborations', [CollectionController::class, 'storeElaboration']);

Route::get('/log', [ActivityLogController::class, 'index']);
Route::post('/log', [ActivityLogController::class, 'store']);

Route::get('/streams/', [StreamController::class, 'index']);
Route::get('/streams/{stream}/formuleblad', [StreamController::class, 'formuleblad']);

    Route::get('/score', [ScoreController::class, 'loadAll']);
    Route::put('/score', [ScoreController::class, 'saveAll']);

    Route::get('/streams/{stream}/scores', [ScoreController::class, 'getStreamScores']);
    Route::post('/streams/{stream}/scores', [ScoreController::class, 'postStreamScore']);

    Route::get('/streams/{stream}', [ScoreController::class, 'getStreamScores']);
    Route::post('/streams/{stream}/{question}', [ScoreController::class, 'postStreamScore']);

    Route::post('/register', [RegistrationController::class, 'register']);
    Route::get('/activation-status', [RegistrationController::class, 'activationStatus']);
    Route::post('/activate-account', [RegistrationController::class, 'activateAccount']);
    Route::post('/activate-license', [RegistrationController::class, 'activateLicense']);

    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']); // csrf?
    Route::post('/reset-password', [NewPasswordController::class, 'save'])->name('password.reset');

    Route::get('/activity_summary/{collection}', [CollectionController::class, 'activity_summary']);

    Route::get('/invite-status', [LicenseController::class, 'getInviteStatus']);
    Route::post('/invite-account', [LicenseController::class, 'postInviteAccount']);
    Route::post('/invite-ok', [LicenseController::class, 'postInviteOk']);

Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::get('/licenses', [LicenseController::class, 'index']);
    Route::post('/licenses', [LicenseController::class, 'index']);
    Route::get('/licenses/{license}', [LicenseController::class, 'get']);
    Route::put('/licenses/{license}', [LicenseController::class, 'put']);
    Route::post('/licenses/{license}/demo-leerling', [LicenseController::class, 'createDemoLeerling']);
    Route::delete('/licenses/{license}', [LicenseController::class, 'delete']);
    Route::post('/licenses/{license}', [LicenseController::class, 'createSeat']);
    Route::get('/licenses/{license}/{seat}', [LicenseController::class, 'getSeat']);
    Route::put('/licenses/{license}/{seat}', [LicenseController::class, 'putSeat']);
    Route::delete('/licenses/{license}/{seat}', [LicenseController::class, 'deleteSeat']);
    Route::post('/licenses/{license}/{seat}', [LicenseController::class, 'createPrivilege']);
    Route::get('/licenses/{license}/{seat}/{privilege}', [LicenseController::class, 'getPrivilege']);
    Route::put('/licenses/{license}/{seat}/{privilege}', [LicenseController::class, 'putPrivilege']);
    Route::delete('/licenses/{license}/{seat}/{privilege}', [LicenseController::class, 'deletePrivilege']);

    Route::get('/groups', [LicenseController::class, 'getGroups']);
    Route::get('/groups/{group}', [LicenseController::class, 'getGroup']);
    Route::put('/groups/{group}', [LicenseController::class, 'putGroup']);


    Route::post('/collections/{collection}/share', [CollectionController::class, 'shareCollection']);


    Route::post('/privilege', [PrivilegeController::class, 'privilege']);
    Route::post('/privileges', [PrivilegeController::class, 'privileges']);
    Route::post('/objects', [PrivilegeController::class, 'objects']);


    Route::get('/latest', [CollectionController::class, 'latest']);
    Route::get('/constraints/{course}', [CollectionController::class, 'constraints']);

    Route::get('/courses/', [CourseController::class, 'index']);
    Route::get('/levels/', [LevelController::class, 'index']);

    Route::get('/streams/{stream}/search/results', [SearchController::class, 'search_results']);
    Route::get('/streams/{stream}/search/', [SearchController::class, 'search']);
    Route::get('/streams/{stream}/tags/', [StreamController::class, 'tags']);
    Route::get('/streams/{stream}/tags/{tag}', [StreamController::class, 'tag']);


    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::get('/user', [AuthenticatedSessionController::class, 'show']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'store']);
    Route::post('/userprofile', [ProfileController::class, 'store_userprofile']);

    //Route::get('/topics/{topic}', [TopicController::class, 'show']);



    Route::get('/cart', [CartController::class, 'index']);

    Route::post('/collections', [CollectionController::class, 'store']);
    
    //Route::get('/download-collection/{collection}', [CollectionController::class, 'showCollectionQuestionsDocument']);
    //Route::get('/download-collection-html/{collection}', [CollectionController::class, 'showCollectionQuestionsHtml']);

    Route::group(['prefix' => 'admin', 'middleware' => 'role:admin,author'], function () {
        Route::get('/', [AdminIndexController::class, 'index']);

        Route::get('/courses', [AdminCourseController::class, 'index']);
        Route::get('/courses/{stream}', [AdminCourseController::class, 'show']);
        Route::put('/courses/{stream}', [AdminCourseController::class, 'update']);
        Route::get('/courses/{stream}/tags', [AdminTagController::class, 'index']);
        Route::get('/courses/{stream}/chapters', [AdminChapterController::class, 'index']);
        Route::post('/courses/{stream}/tags', [AdminTagController::class, 'store']);
        Route::get('courses/{stream}/meta', [AdminCourseController::class, 'showMeta']);

        Route::get('/courses/{stream}/exams', [ExamController::class, 'index']);
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
        Route::post('/questions/{question}/answers', [AnswerController::class, 'store']);

        Route::get('/answers/{answer}', [AnswerController::class, 'show']);
        Route::put('/answers/{answer}', [AnswerController::class, 'update']);
        Route::delete('/answers/{answer}', [AnswerController::class, 'delete']);
        Route::post('/answers/{answer}', [AnswerController::class, 'addStep']);
        Route::put('/answers/{answer}/sections/{answerSection}', [AnswerController::class, 'updateSection']);
        Route::delete('/answers/{answer}/sections/{answerSection}', [AnswerController::class, 'deleteStep']);

        Route::put("/tags/{tag}", [AdminTagController::class, 'update']);
        Route::delete("/tags/{tag}", [AdminTagController::class, 'destroy']);

        Route::put('/tips', [TipController::class, 'update']);

        Route::get('attachments',  [AttachmentController::class, 'index']);
        Route::post('attachments', [AttachmentController::class, 'store']);

        Route::get('/teacher-document/{exam}', [TeacherDocumentController::class, 'index']);

        Route::post('/misc/topics/cache', [TopicController::class, 'cache']);
        Route::post('/misc/answers/fix', [AnswerController::class, 'fix']);
    });

    Route::group(['prefix' => 'admin', 'middleware' => 'role:admin'], function () {
        Route::get('/audit', [AdminAuditController::class, 'index']);

        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
    });
});
