<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Group;
use App\Models\Leaderboard;
use App\Models\Submission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get active events (open status)
        $activeEvents = Event::where('status', 'open')
            ->where('lock_date', '>', now())
            ->orderBy('lock_date', 'asc')
            ->with('creator')
            ->limit(5)
            ->get()
            ->map(function ($event) use ($user) {
                // Check if user has submitted
                $userSubmission = Submission::where('event_id', $event->id)
                    ->where('user_id', $user->id)
                    ->first();

                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'category' => $event->category,
                    'event_date' => $event->event_date,
                    'lock_date' => $event->lock_date,
                    'status' => $event->status,
                    'questions_count' => $event->questions()->count(),
                    'has_submitted' => $userSubmission !== null,
                    'submission_complete' => $userSubmission?->is_complete ?? false,
                ];
            });

        // Get user's groups
        $userGroups = $user->groups()
            ->withCount('users')
            ->limit(5)
            ->get()
            ->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'code' => $group->code,
                    'is_public' => $group->is_public,
                    'members_count' => $group->users_count,
                ];
            });

        // Get recent results (completed events with user submissions)
        $recentResults = Leaderboard::where('user_id', $user->id)
            ->whereHas('event', function ($query) {
                $query->where('status', 'completed');
            })
            ->with(['event', 'group'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($leaderboard) {
                return [
                    'event_name' => $leaderboard->event->name,
                    'group_name' => $leaderboard->group?->name ?? 'Global',
                    'rank' => $leaderboard->rank,
                    'total_score' => $leaderboard->total_score,
                    'possible_points' => $leaderboard->possible_points,
                    'percentage' => $leaderboard->percentage,
                ];
            });

        // Get participation statistics
        $stats = [
            'total_events' => Submission::where('user_id', $user->id)->distinct('event_id')->count('event_id'),
            'total_submissions' => Submission::where('user_id', $user->id)->count(),
            'groups_count' => $user->groups()->count(),
            'average_score' => (float) (Submission::where('user_id', $user->id)
                ->where('is_complete', true)
                ->avg('percentage') ?? 0),
        ];

        return Inertia::render('Dashboard', [
            'activeEvents' => $activeEvents,
            'userGroups' => $userGroups,
            'recentResults' => $recentResults,
            'stats' => $stats,
        ]);
    }
}
