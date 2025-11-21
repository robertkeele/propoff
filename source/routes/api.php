<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Event;
use App\Models\Group;
use App\Models\Submission;
use App\Models\Leaderboard;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Events API
    Route::get('/events', function () {
        return Event::with('creator')
            ->withCount('questions', 'submissions')
            ->latest()
            ->paginate(15);
    });

    Route::get('/events/{event}', function (Event $event) {
        return $event->load(['creator', 'questions']);
    });

    Route::get('/events/available/list', function () {
        return Event::where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('lock_date')
                    ->orWhere('lock_date', '>', now());
            })
            ->with('creator')
            ->withCount('questions')
            ->latest('event_date')
            ->get();
    });

    // Groups API
    Route::get('/groups', function () {
        return auth()->user()->groups()
            ->withCount('users')
            ->latest()
            ->get();
    });

    Route::get('/groups/{group}', function (Group $group) {
        return $group->load(['creator', 'users']);
    });

    // Submissions API
    Route::get('/submissions', function () {
        return Submission::with(['event', 'group'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);
    });

    Route::get('/submissions/{submission}', function (Submission $submission) {
        if ($submission->user_id !== auth()->id()) {
            abort(403);
        }
        return $submission->load(['event', 'userAnswers.question']);
    });

    // Leaderboards API
    Route::get('/leaderboards/event/{event}', function (Event $event) {
        return Leaderboard::with('user')
            ->where('event_id', $event->id)
            ->whereNull('group_id')
            ->orderBy('total_score', 'desc')
            ->limit(100)
            ->get();
    });

    Route::get('/leaderboards/event/{event}/group/{group}', function (Event $event, Group $group) {
        return Leaderboard::with('user')
            ->where('event_id', $event->id)
            ->where('group_id', $group->id)
            ->orderBy('total_score', 'desc')
            ->limit(100)
            ->get();
    });

    Route::get('/leaderboards/user', function () {
        return Leaderboard::with(['event', 'group'])
            ->where('user_id', auth()->id())
            ->orderBy('percentage', 'desc')
            ->get();
    });
});
