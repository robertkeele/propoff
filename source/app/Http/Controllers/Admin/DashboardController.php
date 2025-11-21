<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Group;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(Request $request)
    {
        // Verify user is admin
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized. Admin access required.');
        }

        // Get statistics
        $stats = [
            'total_users' => User::count(),
            'total_events' => Event::count(),
            'total_groups' => Group::count(),
            'total_submissions' => Submission::count(),
            'total_questions' => \App\Models\EventQuestion::count(),
            'active_events' => Event::where('status', 'open')->count(),
            'completed_events' => Event::where('status', 'completed')->count(),
            'draft_events' => Event::where('status', 'draft')->count(),
            'completed_submissions' => Submission::where('is_complete', true)->count(),
        ];

        // Get recent events
        $recentEvents = Event::with('creator')
            ->withCount(['eventQuestions', 'submissions'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'category' => $event->category,
                    'status' => $event->status,
                    'event_date' => $event->event_date,
                    'lock_date' => $event->lock_date,
                    'creator_name' => $event->creator->name,
                    'questions_count' => $event->questions_count,
                    'submissions_count' => $event->submissions_count,
                    'created_at' => $event->created_at,
                ];
            });

        // Get recent submissions
        $recentSubmissions = Submission::with(['user', 'event', 'group'])
            ->where('is_complete', true)
            ->latest('submitted_at')
            ->limit(10)
            ->get()
            ->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'user_name' => $submission->user->name,
                    'event_name' => $submission->event->name,
                    'group_name' => $submission->group->name,
                    'total_score' => $submission->total_score,
                    'possible_points' => $submission->possible_points,
                    'percentage' => $submission->percentage,
                    'submitted_at' => $submission->submitted_at,
                ];
            });

        // Get recent users
        $recentUsers = User::latest()
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                ];
            });

        // Events by status
        $eventsByStatus = [
            'draft' => Event::where('status', 'draft')->count(),
            'open' => Event::where('status', 'open')->count(),
            'locked' => Event::where('status', 'locked')->count(),
            'in_progress' => Event::where('status', 'in_progress')->count(),
            'completed' => Event::where('status', 'completed')->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentEvents' => $recentEvents,
            'recentSubmissions' => $recentSubmissions,
            'recentUsers' => $recentUsers,
            'eventsByStatus' => $eventsByStatus,
        ]);
    }
}
