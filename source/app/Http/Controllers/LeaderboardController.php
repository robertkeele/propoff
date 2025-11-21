<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Group;
use App\Models\Leaderboard;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    /**
     * Display the global leaderboard for an event.
     */
    public function event(Event $event)
    {
        $leaderboard = Leaderboard::with('user')
            ->where('event_id', $event->id)
            ->whereNull('group_id')
            ->orderBy('total_score', 'desc')
            ->orderBy('answered_count', 'desc')
            ->paginate(50);

        // Update ranks
        $this->updateRanks($event->id, null);

        return Inertia::render('Leaderboards/Event', [
            'event' => $event,
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Display the leaderboard for a specific group and event.
     */
    public function group(Event $event, Group $group)
    {
        $leaderboard = Leaderboard::with('user')
            ->where('event_id', $event->id)
            ->where('group_id', $group->id)
            ->orderBy('total_score', 'desc')
            ->orderBy('answered_count', 'desc')
            ->paginate(50);

        // Update ranks
        $this->updateRanks($event->id, $group->id);

        return Inertia::render('Leaderboards/Group', [
            'event' => $event,
            'group' => $group,
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Display all leaderboards for all events.
     */
    public function index()
    {
        $events = Event::where('status', 'open')
            ->orWhere('status', 'completed')
            ->with(['leaderboards' => function ($query) {
                $query->whereNull('group_id')
                    ->orderBy('rank')
                    ->limit(10);
            }, 'leaderboards.user'])
            ->withCount('submissions')
            ->latest('event_date')
            ->paginate(10);

        return Inertia::render('Leaderboards/Index', [
            'events' => $events,
        ]);
    }

    /**
     * Display user's position across all leaderboards.
     */
    public function user()
    {
        $userLeaderboards = Leaderboard::with(['event', 'group'])
            ->where('user_id', auth()->id())
            ->orderBy('percentage', 'desc')
            ->paginate(15);

        return Inertia::render('Leaderboards/User', [
            'leaderboards' => $userLeaderboards,
        ]);
    }

    /**
     * Update ranks for a specific event/group leaderboard.
     */
    protected function updateRanks($eventId, $groupId = null)
    {
        $query = Leaderboard::where('event_id', $eventId);
        if ($groupId) {
            $query->where('group_id', $groupId);
        } else {
            $query->whereNull('group_id');
        }

        $entries = $query->orderBy('total_score', 'desc')
            ->orderBy('answered_count', 'desc')
            ->get();

        $rank = 1;
        foreach ($entries as $entry) {
            $entry->update(['rank' => $rank]);
            $rank++;
        }
    }

    /**
     * Recalculate leaderboard for an event.
     */
    public function recalculate(Event $event)
    {
        $this->authorize('update', $event);
        // Recalculate from submissions
        $submissions = $event->submissions()
            ->where('is_complete', true)
            ->with('userAnswers')
            ->get();

        foreach ($submissions as $submission) {
            $totalScore = $submission->userAnswers->sum('points_earned');
            $answeredCount = $submission->userAnswers->count();
            $percentage = $submission->possible_points > 0
                ? ($totalScore / $submission->possible_points) * 100
                : 0;

            Leaderboard::updateOrCreate(
                [
                    'event_id' => $submission->event_id,
                    'user_id' => $submission->user_id,
                    'group_id' => $submission->group_id,
                ],
                [
                    'total_score' => $totalScore,
                    'possible_points' => $submission->possible_points,
                    'percentage' => $percentage,
                    'answered_count' => $answeredCount,
                ]
            );
        }

        // Update ranks
        $this->updateRanks($event->id, null);

        // Update ranks for all groups
        $groups = $event->submissions()
            ->whereNotNull('group_id')
            ->distinct()
            ->pluck('group_id');

        foreach ($groups as $groupId) {
            $this->updateRanks($event->id, $groupId);
        }

        return back()->with('success', 'Leaderboard recalculated successfully!');
    }
}
