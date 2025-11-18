<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\Admin\QuestionTemplateController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
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
    return Inertia::render('Welcome', [
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

    // Game routes
    Route::resource('games', GameController::class);
    Route::get('/games-available', [GameController::class, 'available'])->name('games.available');
    Route::get('/games/{game}/play', [GameController::class, 'play'])->name('games.play');

    // Question routes (nested under games)
    Route::get('/games/{game}/questions', [QuestionController::class, 'index'])->name('games.questions.index');
    Route::get('/games/{game}/questions/create', [QuestionController::class, 'create'])->name('games.questions.create');
    Route::post('/games/{game}/questions', [QuestionController::class, 'store'])->name('games.questions.store');
    Route::post('/games/{game}/questions/template/{template}', [QuestionController::class, 'createFromTemplate'])->name('games.questions.createFromTemplate');
    Route::get('/games/{game}/questions/{question}', [QuestionController::class, 'show'])->name('games.questions.show');
    Route::get('/games/{game}/questions/{question}/edit', [QuestionController::class, 'edit'])->name('games.questions.edit');
    Route::patch('/games/{game}/questions/{question}', [QuestionController::class, 'update'])->name('games.questions.update');
    Route::delete('/games/{game}/questions/{question}', [QuestionController::class, 'destroy'])->name('games.questions.destroy');
    Route::post('/games/{game}/questions/reorder', [QuestionController::class, 'reorder'])->name('games.questions.reorder');

    // Submission routes
    Route::get('/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::post('/games/{game}/submissions/start', [SubmissionController::class, 'start'])->name('submissions.start');
    Route::get('/submissions/{submission}/continue', [SubmissionController::class, 'continue'])->name('submissions.continue');
    Route::post('/submissions/{submission}/answers', [SubmissionController::class, 'saveAnswers'])->name('submissions.saveAnswers');
    Route::post('/submissions/{submission}/submit', [SubmissionController::class, 'submit'])->name('submissions.submit');
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::delete('/submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');

    // Leaderboard routes
    Route::get('/leaderboards', [LeaderboardController::class, 'index'])->name('leaderboards.index');
    Route::get('/leaderboards/user', [LeaderboardController::class, 'user'])->name('leaderboards.user');
    Route::get('/leaderboards/game/{game}', [LeaderboardController::class, 'game'])->name('leaderboards.game');
    Route::get('/leaderboards/game/{game}/group/{group}', [LeaderboardController::class, 'group'])->name('leaderboards.group');
    Route::post('/leaderboards/game/{game}/recalculate', [LeaderboardController::class, 'recalculate'])->name('leaderboards.recalculate');

    // Group routes
    Route::resource('groups', GroupController::class);
    Route::post('/groups/join', [GroupController::class, 'join'])->name('groups.join');
    Route::post('/groups/{group}/leave', [GroupController::class, 'leave'])->name('groups.leave');
    Route::delete('/groups/{group}/members/{user}', [GroupController::class, 'removeMember'])->name('groups.removeMember');
    Route::post('/groups/{group}/regenerate-code', [GroupController::class, 'regenerateCode'])->name('groups.regenerateCode');
});

// Admin routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Games
    Route::resource('games', AdminGameController::class);
    Route::post('/games/{game}/update-status', [AdminGameController::class, 'updateStatus'])->name('games.updateStatus');
    Route::post('/games/{game}/duplicate', [AdminGameController::class, 'duplicate'])->name('games.duplicate');
    Route::get('/games/{game}/statistics', [AdminGameController::class, 'statistics'])->name('games.statistics');
    
    // Game Invitations
    Route::post('/games/{game}/generate-invitation', [AdminGameController::class, 'generateInvitation'])->name('games.generateInvitation');
    Route::post('/games/{game}/invitations/{invitation}/deactivate', [AdminGameController::class, 'deactivateInvitation'])->name('games.deactivateInvitation');

    // Question Templates
    Route::resource('question-templates', QuestionTemplateController::class);
    Route::post('/question-templates/{template}/preview', [QuestionTemplateController::class, 'preview'])->name('question-templates.preview');
    Route::post('/question-templates/{template}/duplicate', [QuestionTemplateController::class, 'duplicate'])->name('question-templates.duplicate');

    // Questions
    Route::get('/games/{game}/questions', [AdminQuestionController::class, 'index'])->name('games.questions.index');
    Route::get('/games/{game}/questions/create', [AdminQuestionController::class, 'create'])->name('games.questions.create');
    Route::post('/games/{game}/questions', [AdminQuestionController::class, 'store'])->name('games.questions.store');
    Route::post('/games/{game}/questions/template/{template}', [AdminQuestionController::class, 'createFromTemplate'])->name('games.questions.createFromTemplate');
    Route::get('/games/{game}/questions/{question}/edit', [AdminQuestionController::class, 'edit'])->name('games.questions.edit');
    Route::patch('/games/{game}/questions/{question}', [AdminQuestionController::class, 'update'])->name('games.questions.update');
    Route::delete('/games/{game}/questions/{question}', [AdminQuestionController::class, 'destroy'])->name('games.questions.destroy');
    Route::post('/games/{game}/questions/reorder', [AdminQuestionController::class, 'reorder'])->name('games.questions.reorder');
    Route::post('/games/{game}/questions/{question}/duplicate', [AdminQuestionController::class, 'duplicate'])->name('games.questions.duplicate');
    Route::post('/games/{game}/questions/bulk-import', [AdminQuestionController::class, 'bulkImport'])->name('games.questions.bulkImport');

    // Grading
    Route::get('/games/{game}/grading', [GradingController::class, 'index'])->name('games.grading.index');
    Route::post('/games/{game}/questions/{question}/set-answer', [GradingController::class, 'setAnswer'])->name('games.grading.setAnswer');
    Route::post('/games/{game}/groups/{group}/bulk-set-answers', [GradingController::class, 'bulkSetAnswers'])->name('games.grading.bulkSetAnswers');
    Route::post('/games/{game}/questions/{question}/groups/{group}/toggle-void', [GradingController::class, 'toggleVoid'])->name('games.grading.toggleVoid');
    Route::post('/games/{game}/calculate-scores', [GradingController::class, 'calculateScores'])->name('games.grading.calculateScores');
    Route::get('/games/{game}/export-csv', [GradingController::class, 'exportCSV'])->name('games.grading.exportCSV');
    Route::get('/games/{game}/export-detailed-csv', [GradingController::class, 'exportDetailedCSV'])->name('games.grading.exportDetailedCSV');
    Route::get('/games/{game}/groups/{group}/export-detailed-csv', [GradingController::class, 'exportDetailedCSV'])->name('games.grading.exportDetailedCSVByGroup');
    Route::get('/games/{game}/groups/{group}/summary', [GradingController::class, 'groupSummary'])->name('games.grading.groupSummary');

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
    Route::get('/groups/{group}', [AdminGroupController::class, 'show'])->name('groups.show');
    Route::patch('/groups/{group}', [AdminGroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [AdminGroupController::class, 'destroy'])->name('groups.destroy');
    Route::post('/groups/{group}/add-user', [AdminGroupController::class, 'addUser'])->name('groups.addUser');
    Route::delete('/groups/{group}/users/{user}', [AdminGroupController::class, 'removeUser'])->name('groups.removeUser');
    Route::get('/groups/export/csv', [AdminGroupController::class, 'exportCSV'])->name('groups.exportCSV');
    Route::get('/groups-statistics', [AdminGroupController::class, 'statistics'])->name('groups.statistics');
    Route::get('/groups/{group}/members', [AdminGroupController::class, 'members'])->name('groups.members');
    Route::post('/groups/bulk-delete', [AdminGroupController::class, 'bulkDelete'])->name('groups.bulkDelete');
});

require __DIR__.'/auth.php';
