<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventQuestionController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\QuestionTemplateController;
use App\Http\Controllers\Admin\EventQuestionController as AdminEventQuestionController;
use App\Http\Controllers\Admin\GradingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\GroupController as AdminGroupController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
    return Inertia::render('Index', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Guest routes (public - no auth required)
Route::get('/join/{token}', [GuestController::class, 'show'])->name('guest.join');
Route::post('/join/{token}', [GuestController::class, 'register'])->name('guest.register');
Route::get('/my-results/{guestToken}', [GuestController::class, 'results'])->name('guest.results');

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Event routes (user-facing - read only)
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('/events-available', [EventController::class, 'available'])->name('events.available');
    Route::get('/events/{event}/play', [EventController::class, 'play'])->name('events.play');

    // Submission routes
    Route::get('/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::post('/events/{event}/submissions/start', [SubmissionController::class, 'start'])->name('submissions.start');
    Route::get('/submissions/{submission}/continue', [SubmissionController::class, 'continue'])->name('submissions.continue');
    Route::post('/submissions/{submission}/answers', [SubmissionController::class, 'saveAnswers'])->name('submissions.saveAnswers');
    Route::post('/submissions/{submission}/submit', [SubmissionController::class, 'submit'])->name('submissions.submit');
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::get('/submissions/{submission}/confirmation', [SubmissionController::class, 'confirmation'])->name('submissions.confirmation');
    Route::delete('/submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');

    // Leaderboard routes
    Route::get('/leaderboards', [LeaderboardController::class, 'index'])->name('leaderboards.index');
    Route::get('/leaderboards/user', [LeaderboardController::class, 'user'])->name('leaderboards.user');
    Route::get('/leaderboards/event/{event}', [LeaderboardController::class, 'event'])->name('leaderboards.event');
    Route::get('/leaderboards/event/{event}/group/{group}', [LeaderboardController::class, 'group'])->name('leaderboards.group');
    Route::post('/leaderboards/event/{event}/recalculate', [LeaderboardController::class, 'recalculate'])->name('leaderboards.recalculate');

    // Group routes
    Route::resource('groups', GroupController::class);
    Route::post('/groups/join', [GroupController::class, 'join'])->name('groups.join');
    Route::post('/groups/{group}/leave', [GroupController::class, 'leave'])->name('groups.leave');
    Route::delete('/groups/{group}/members/{user}', [GroupController::class, 'removeMember'])->name('groups.removeMember');
    Route::post('/groups/{group}/regenerate-code', [GroupController::class, 'regenerateCode'])->name('groups.regenerateCode');
});

// Captain routes
Route::prefix('captain')->name('captain.')->middleware(['auth'])->group(function () {
    // Captain Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Captain\DashboardController::class, 'index'])->name('dashboard');

    // Create Group from Captain Invitation (public - any authenticated user)
    Route::get('/events/{event}/create-group/{token}', [\App\Http\Controllers\Captain\GroupController::class, 'create'])->name('groups.create');
    Route::post('/events/{event}/create-group/{token}', [\App\Http\Controllers\Captain\GroupController::class, 'store'])->name('groups.store');

    // Captain Group Management (requires captain of specific group)
    Route::middleware(\App\Http\Middleware\EnsureIsCaptainOfGroup::class)->group(function () {
        Route::get('/groups/{group}', [\App\Http\Controllers\Captain\GroupController::class, 'show'])->name('groups.show');
        Route::patch('/groups/{group}', [\App\Http\Controllers\Captain\GroupController::class, 'update'])->name('groups.update');
        Route::delete('/groups/{group}', [\App\Http\Controllers\Captain\GroupController::class, 'destroy'])->name('groups.destroy');

        // Question Management
        Route::get('/groups/{group}/questions', [\App\Http\Controllers\Captain\GroupQuestionController::class, 'index'])->name('groups.questions.index');
        Route::get('/groups/{group}/questions/create', [\App\Http\Controllers\Captain\GroupQuestionController::class, 'create'])->name('groups.questions.create');
        Route::post('/groups/{group}/questions', [\App\Http\Controllers\Captain\GroupQuestionController::class, 'store'])->name('groups.questions.store');
        Route::get('/groups/{group}/questions/{groupQuestion}/edit', [\App\Http\Controllers\Captain\GroupQuestionController::class, 'edit'])->name('groups.questions.edit');
        Route::patch('/groups/{group}/questions/{groupQuestion}', [\App\Http\Controllers\Captain\GroupQuestionController::class, 'update'])->name('groups.questions.update');
        Route::delete('/groups/{group}/questions/{groupQuestion}', [\App\Http\Controllers\Captain\GroupQuestionController::class, 'destroy'])->name('groups.questions.destroy');
        Route::post('/groups/{group}/questions/{groupQuestion}/toggle-active', [\App\Http\Controllers\Captain\GroupQuestionController::class, 'toggleActive'])->name('groups.questions.toggleActive');
        Route::post('/groups/{group}/questions/{groupQuestion}/duplicate', [\App\Http\Controllers\Captain\GroupQuestionController::class, 'duplicate'])->name('groups.questions.duplicate');
        Route::post('/groups/{group}/questions/reorder', [\App\Http\Controllers\Captain\GroupQuestionController::class, 'reorder'])->name('groups.questions.reorder');

        // Grading
        Route::get('/groups/{group}/grading', [\App\Http\Controllers\Captain\GradingController::class, 'index'])->name('groups.grading.index');
        Route::post('/groups/{group}/questions/{groupQuestion}/set-answer', [\App\Http\Controllers\Captain\GradingController::class, 'setAnswer'])->name('groups.grading.setAnswer');
        Route::post('/groups/{group}/grading/bulk-set-answers', [\App\Http\Controllers\Captain\GradingController::class, 'bulkSetAnswers'])->name('groups.grading.bulkSetAnswers');
        Route::post('/groups/{group}/questions/{groupQuestion}/toggle-void', [\App\Http\Controllers\Captain\GradingController::class, 'toggleVoid'])->name('groups.grading.toggleVoid');

        // Member Management
        Route::get('/groups/{group}/members', [\App\Http\Controllers\Captain\MemberController::class, 'index'])->name('groups.members.index');
        Route::post('/groups/{group}/members/{user}/promote', [\App\Http\Controllers\Captain\MemberController::class, 'promoteToCaptain'])->name('groups.members.promote');
        Route::post('/groups/{group}/members/{user}/demote', [\App\Http\Controllers\Captain\MemberController::class, 'demoteFromCaptain'])->name('groups.members.demote');
        Route::delete('/groups/{group}/members/{user}', [\App\Http\Controllers\Captain\MemberController::class, 'remove'])->name('groups.members.remove');
        Route::post('/groups/{group}/regenerate-join-code', [\App\Http\Controllers\Captain\MemberController::class, 'regenerateJoinCode'])->name('groups.members.regenerateJoinCode');
    });
});

// Admin routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Events
    Route::resource('events', AdminEventController::class);
    Route::post('/events/{event}/update-status', [AdminEventController::class, 'updateStatus'])->name('events.updateStatus');
    Route::post('/events/{event}/duplicate', [AdminEventController::class, 'duplicate'])->name('events.duplicate');
    Route::get('/events/{event}/statistics', [AdminEventController::class, 'statistics'])->name('events.statistics');

    // Event Invitations
    Route::post('/events/{event}/generate-invitation', [AdminEventController::class, 'generateInvitation'])->name('events.generateInvitation');
    Route::post('/events/{event}/invitations/{invitation}/deactivate', [AdminEventController::class, 'deactivateInvitation'])->name('events.deactivateInvitation');

    // Question Templates
    Route::resource('question-templates', QuestionTemplateController::class);
    Route::post('/question-templates/{template}/preview', [QuestionTemplateController::class, 'preview'])->name('question-templates.preview');
    Route::post('/question-templates/{template}/duplicate', [QuestionTemplateController::class, 'duplicate'])->name('question-templates.duplicate');

    // Event Questions
    Route::get('/events/{event}/event-questions', [AdminEventQuestionController::class, 'index'])->name('events.event-questions.index');
    Route::get('/events/{event}/event-questions/create', [AdminEventQuestionController::class, 'create'])->name('events.event-questions.create');
    Route::post('/events/{event}/event-questions', [AdminEventQuestionController::class, 'store'])->name('events.event-questions.store');
    Route::post('/events/{event}/event-questions/template/{template}', [AdminEventQuestionController::class, 'createFromTemplate'])->name('events.event-questions.createFromTemplate');
    Route::post('/events/{event}/event-questions/bulk-create-from-templates', [AdminEventQuestionController::class, 'bulkCreateFromTemplates'])->name('events.event-questions.bulkCreateFromTemplates');
    Route::get('/events/{event}/event-questions/{eventQuestion}/edit', [AdminEventQuestionController::class, 'edit'])->name('events.event-questions.edit');
    Route::patch('/events/{event}/event-questions/{eventQuestion}', [AdminEventQuestionController::class, 'update'])->name('events.event-questions.update');
    Route::delete('/events/{event}/event-questions/{eventQuestion}', [AdminEventQuestionController::class, 'destroy'])->name('events.event-questions.destroy');
    Route::post('/events/{event}/event-questions/reorder', [AdminEventQuestionController::class, 'reorder'])->name('events.event-questions.reorder');
    Route::post('/events/{event}/event-questions/{eventQuestion}/duplicate', [AdminEventQuestionController::class, 'duplicate'])->name('events.event-questions.duplicate');
    Route::post('/events/{event}/event-questions/bulk-import', [AdminEventQuestionController::class, 'bulkImport'])->name('events.event-questions.bulkImport');

    // Grading
    Route::get('/events/{event}/grading', [GradingController::class, 'index'])->name('events.grading.index');
    Route::post('/events/{event}/event-questions/{eventQuestion}/set-answer', [GradingController::class, 'setAnswer'])->name('events.grading.setAnswer');
    Route::post('/events/{event}/groups/{group}/bulk-set-answers', [GradingController::class, 'bulkSetAnswers'])->name('events.grading.bulkSetAnswers');
    Route::post('/events/{event}/event-questions/{eventQuestion}/groups/{group}/toggle-void', [GradingController::class, 'toggleVoid'])->name('events.grading.toggleVoid');
    Route::post('/events/{event}/calculate-scores', [GradingController::class, 'calculateScores'])->name('events.grading.calculateScores');
    Route::get('/events/{event}/export-csv', [GradingController::class, 'exportCSV'])->name('events.grading.exportCSV');
    Route::get('/events/{event}/export-detailed-csv', [GradingController::class, 'exportDetailedCSV'])->name('events.grading.exportDetailedCSV');
    Route::get('/events/{event}/groups/{group}/export-detailed-csv', [GradingController::class, 'exportDetailedCSV'])->name('events.grading.exportDetailedCSVByGroup');
    Route::get('/events/{event}/groups/{group}/summary', [GradingController::class, 'groupSummary'])->name('events.grading.groupSummary');

    // Users
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/update-role', [AdminUserController::class, 'updateRole'])->name('users.updateRole');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/{user}/activity', [AdminUserController::class, 'activity'])->name('users.activity');
    Route::get('/users/export/csv', [AdminUserController::class, 'exportCSV'])->name('users.exportCSV');
    Route::post('/users/bulk-delete', [AdminUserController::class, 'bulkDelete'])->name('users.bulkDelete');
    Route::get('/users-statistics', [AdminUserController::class, 'statistics'])->name('users.statistics');

    // Groups
    Route::get('/groups', [AdminGroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create', [AdminGroupController::class, 'create'])->name('groups.create');
    Route::post('/groups', [AdminGroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}', [AdminGroupController::class, 'show'])->name('groups.show');
    Route::get('/groups/{group}/edit', [AdminGroupController::class, 'edit'])->name('groups.edit');
    Route::patch('/groups/{group}', [AdminGroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [AdminGroupController::class, 'destroy'])->name('groups.destroy');
    Route::post('/groups/{group}/add-user', [AdminGroupController::class, 'addUser'])->name('groups.addUser');
    Route::delete('/groups/{group}/users/{user}', [AdminGroupController::class, 'removeUser'])->name('groups.removeUser');
    Route::get('/groups/export/csv', [AdminGroupController::class, 'exportCSV'])->name('groups.exportCSV');
    Route::get('/groups-statistics', [AdminGroupController::class, 'statistics'])->name('groups.statistics');
    Route::get('/groups/{group}/members', [AdminGroupController::class, 'members'])->name('groups.members');
    Route::post('/groups/bulk-delete', [AdminGroupController::class, 'bulkDelete'])->name('groups.bulkDelete');

    // Captain Invitations
    Route::get('/events/{event}/captain-invitations', [\App\Http\Controllers\Admin\CaptainInvitationController::class, 'index'])->name('events.captain-invitations.index');
    Route::post('/events/{event}/captain-invitations', [\App\Http\Controllers\Admin\CaptainInvitationController::class, 'store'])->name('events.captain-invitations.store');
    Route::get('/events/{event}/captain-invitations/{invitation}', [\App\Http\Controllers\Admin\CaptainInvitationController::class, 'show'])->name('events.captain-invitations.show');
    Route::patch('/events/{event}/captain-invitations/{invitation}', [\App\Http\Controllers\Admin\CaptainInvitationController::class, 'update'])->name('events.captain-invitations.update');
    Route::delete('/events/{event}/captain-invitations/{invitation}', [\App\Http\Controllers\Admin\CaptainInvitationController::class, 'destroy'])->name('events.captain-invitations.destroy');
    Route::post('/events/{event}/captain-invitations/{invitation}/deactivate', [\App\Http\Controllers\Admin\CaptainInvitationController::class, 'deactivate'])->name('events.captain-invitations.deactivate');
    Route::post('/events/{event}/captain-invitations/{invitation}/reactivate', [\App\Http\Controllers\Admin\CaptainInvitationController::class, 'reactivate'])->name('events.captain-invitations.reactivate');
    Route::post('/events/{event}/generate-captain-invitation', [AdminEventController::class, 'generateCaptainInvitation'])->name('events.generateCaptainInvitation');

    // Event Answers (Admin-level grading)
    Route::get('/events/{event}/event-answers', [\App\Http\Controllers\Admin\EventAnswerController::class, 'index'])->name('events.event-answers.index');
    Route::post('/events/{event}/event-questions/{eventQuestion}/set-event-answer', [\App\Http\Controllers\Admin\EventAnswerController::class, 'setAnswer'])->name('events.event-answers.setAnswer');
    Route::post('/events/{event}/event-answers/bulk-set', [\App\Http\Controllers\Admin\EventAnswerController::class, 'bulkSetAnswers'])->name('events.event-answers.bulkSetAnswers');
    Route::post('/events/{event}/event-questions/{eventQuestion}/toggle-event-void', [\App\Http\Controllers\Admin\EventAnswerController::class, 'toggleVoid'])->name('events.event-answers.toggleVoid');
    Route::delete('/events/{event}/event-questions/{eventQuestion}/clear-event-answer', [\App\Http\Controllers\Admin\EventAnswerController::class, 'clearAnswer'])->name('events.event-answers.clearAnswer');
});

require __DIR__.'/auth.php';
