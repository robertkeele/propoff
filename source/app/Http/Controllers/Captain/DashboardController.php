<?php

namespace App\Http\Controllers\Captain;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the captain dashboard showing all groups they manage.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get all groups where user is a captain
        $captainGroups = $user->captainGroups()
            ->with(['event', 'members'])
            ->withCount(['members', 'submissions', 'groupQuestions'])
            ->get()
            ->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'event' => [
                        'id' => $group->event->id,
                        'name' => $group->event->name,
                        'status' => $group->event->status,
                        'event_date' => $group->event->event_date,
                        'lock_date' => $group->event->lock_date,
                    ],
                    'grading_source' => $group->grading_source,
                    'members_count' => $group->members_count,
                    'submissions_count' => $group->submissions_count,
                    'questions_count' => $group->group_questions_count,
                    'join_code' => $group->code,
                    'created_at' => $group->created_at,
                ];
            });

        return Inertia::render('Captain/Dashboard', [
            'captainGroups' => $captainGroups,
        ]);
    }
}
