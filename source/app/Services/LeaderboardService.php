<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Group;
use App\Models\Leaderboard;
use App\Models\Submission;

class LeaderboardService
{
    /**
     * Update leaderboard for a specific submission.
     */
    public function updateLeaderboardForSubmission(Submission $submission): void
    {
        $answeredCount = $submission->userAnswers()->count();

        Leaderboard::updateOrCreate(
            [
                'event_id' => $submission->event_id,
                'user_id' => $submission->user_id,
                'group_id' => $submission->group_id,
            ],
            [
                'total_score' => $submission->total_score,
                'possible_points' => $submission->possible_points,
                'percentage' => $submission->percentage,
                'answered_count' => $answeredCount,
                'rank' => 0, // Will be calculated in updateRanks()
            ]
        );
    }

    /**
     * Update ranks for a specific event and group leaderboard.
     */
    public function updateRanks(int $eventId, ?int $groupId = null): void
    {
        $query = Leaderboard::where('event_id', $eventId);
        if ($groupId) {
            $query->where('group_id', $groupId);
        } else {
            $query->whereNull('group_id');
        }

        // Order by percentage DESC, then total_score DESC, then answered_count DESC
        $entries = $query->orderByDesc('percentage')
            ->orderByDesc('total_score')
            ->orderByDesc('answered_count')
            ->get();

        $rank = 1;
        $previousPercentage = null;
        $previousScore = null;
        $previousCount = null;
        $sameRankCount = 0;

        foreach ($entries as $index => $entry) {
            // Check if this entry has the same score as previous (tie)
            if ($previousPercentage === $entry->percentage
                && $previousScore === $entry->total_score
                && $previousCount === $entry->answered_count) {
                // Same rank as previous
                $sameRankCount++;
            } else {
                // Different rank
                $rank = $index + 1;
                $sameRankCount = 0;
            }

            $entry->update(['rank' => $rank]);

            $previousPercentage = $entry->percentage;
            $previousScore = $entry->total_score;
            $previousCount = $entry->answered_count;
        }
    }

    /**
     * Recalculate all leaderboards for an event.
     */
    public function recalculateEventLeaderboards(Event $event): void
    {
        // Get all completed submissions for the event
        $submissions = $event->submissions()
            ->where('is_complete', true)
            ->with('userAnswers')
            ->get();

        // Update leaderboard entries
        foreach ($submissions as $submission) {
            $this->updateLeaderboardForSubmission($submission);
        }

        // Update global leaderboard ranks
        $this->updateRanks($event->id, null);

        // Update ranks for each group
        $groupIds = $event->submissions()
            ->whereNotNull('group_id')
            ->distinct()
            ->pluck('group_id');

        foreach ($groupIds as $groupId) {
            $this->updateRanks($event->id, $groupId);
        }

        // Create/update global leaderboard (aggregate across groups)
        $this->createGlobalLeaderboard($event);
    }

    /**
     * Create global leaderboard by aggregating group performances.
     */
    protected function createGlobalLeaderboard(Event $event): void
    {
        // Get all users who participated in the event
        $userIds = $event->submissions()
            ->where('is_complete', true)
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            // Get all submissions for this user across all groups
            $userSubmissions = $event->submissions()
                ->where('user_id', $userId)
                ->where('is_complete', true)
                ->get();

            // Aggregate scores
            $totalScore = $userSubmissions->sum('total_score');
            $possiblePoints = $userSubmissions->sum('possible_points');
            $answeredCount = $userSubmissions->sum(function ($submission) {
                return $submission->userAnswers()->count();
            });

            $percentage = $possiblePoints > 0
                ? round(($totalScore / $possiblePoints) * 100, 2)
                : 0;

            // Create or update global leaderboard entry
            Leaderboard::updateOrCreate(
                [
                    'event_id' => $event->id,
                    'user_id' => $userId,
                    'group_id' => null, // Global leaderboard
                ],
                [
                    'total_score' => $totalScore,
                    'possible_points' => $possiblePoints,
                    'percentage' => $percentage,
                    'answered_count' => $answeredCount,
                    'rank' => 0, // Will be updated by updateRanks()
                ]
            );
        }

        // Update global ranks
        $this->updateRanks($event->id, null);
    }

    /**
     * Get leaderboard for a specific event and group.
     */
    public function getLeaderboard(Event $event, ?Group $group = null, int $limit = 50)
    {
        $query = Leaderboard::with('user')
            ->where('event_id', $event->id);

        if ($group) {
            $query->where('group_id', $group->id);
        } else {
            $query->whereNull('group_id');
        }

        return $query->orderBy('rank')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's rank in a specific leaderboard.
     */
    public function getUserRank(Event $event, int $userId, ?int $groupId = null): ?int
    {
        $leaderboard = Leaderboard::where('event_id', $event->id)
            ->where('user_id', $userId);

        if ($groupId) {
            $leaderboard->where('group_id', $groupId);
        } else {
            $leaderboard->whereNull('group_id');
        }

        $entry = $leaderboard->first();

        return $entry?->rank;
    }

    /**
     * Get top performers for an event.
     */
    public function getTopPerformers(Event $event, int $limit = 10, ?int $groupId = null)
    {
        $query = Leaderboard::with('user')
            ->where('event_id', $event->id);

        if ($groupId) {
            $query->where('group_id', $groupId);
        } else {
            $query->whereNull('group_id');
        }

        return $query->orderBy('rank')
            ->limit($limit)
            ->get();
    }

    /**
     * Get leaderboard statistics for an event.
     */
    public function getLeaderboardStats(Event $event, ?int $groupId = null): array
    {
        $query = Leaderboard::where('event_id', $event->id);
        if ($groupId) {
            $query->where('group_id', $groupId);
        } else {
            $query->whereNull('group_id');
        }

        $entries = $query->get();

        return [
            'total_participants' => $entries->count(),
            'average_score' => $entries->avg('percentage') ?? 0,
            'highest_score' => $entries->max('percentage') ?? 0,
            'lowest_score' => $entries->min('percentage') ?? 0,
            'median_score' => $this->calculateMedian($entries->pluck('percentage')->toArray()),
        ];
    }

    /**
     * Calculate median value.
     */
    protected function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0;
        }

        sort($values);
        $count = count($values);
        $middle = floor($count / 2);

        if ($count % 2 == 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }

        return $values[$middle];
    }

    /**
     * Update leaderboard for a specific event and group.
     * Used when answers are updated (captain or admin grading).
     */
    public function updateLeaderboard(Event $event, Group $group): void
    {
        // Get all completed submissions for this group
        $submissions = $event->submissions()
            ->where('group_id', $group->id)
            ->where('is_complete', true)
            ->with('userAnswers')
            ->get();

        // Update leaderboard entries for all submissions
        foreach ($submissions as $submission) {
            $this->updateLeaderboardForSubmission($submission);
        }

        // Update ranks for this group
        $this->updateRanks($event->id, $group->id);

        // Also update global leaderboard
        $this->createGlobalLeaderboard($event);
    }
}
