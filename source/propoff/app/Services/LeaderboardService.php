<?php

namespace App\Services;

use App\Models\Game;
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
                'game_id' => $submission->game_id,
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
     * Update ranks for a specific game and group leaderboard.
     */
    public function updateRanks(int $gameId, ?int $groupId = null): void
    {
        $query = Leaderboard::where('game_id', $gameId);

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
     * Recalculate all leaderboards for a game.
     */
    public function recalculateGameLeaderboards(Game $game): void
    {
        // Get all completed submissions for the game
        $submissions = $game->submissions()
            ->where('is_complete', true)
            ->with('userAnswers')
            ->get();

        // Update leaderboard entries
        foreach ($submissions as $submission) {
            $this->updateLeaderboardForSubmission($submission);
        }

        // Update global leaderboard ranks
        $this->updateRanks($game->id, null);

        // Update ranks for each group
        $groupIds = $game->submissions()
            ->whereNotNull('group_id')
            ->distinct()
            ->pluck('group_id');

        foreach ($groupIds as $groupId) {
            $this->updateRanks($game->id, $groupId);
        }

        // Create/update global leaderboard (aggregate across groups)
        $this->createGlobalLeaderboard($game);
    }

    /**
     * Create global leaderboard by aggregating group performances.
     */
    protected function createGlobalLeaderboard(Game $game): void
    {
        // Get all users who participated in the game
        $userIds = $game->submissions()
            ->where('is_complete', true)
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            // Get all submissions for this user across all groups
            $userSubmissions = $game->submissions()
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
                    'game_id' => $game->id,
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
        $this->updateRanks($game->id, null);
    }

    /**
     * Get leaderboard for a specific game and group.
     */
    public function getLeaderboard(Game $game, ?Group $group = null, int $limit = 50)
    {
        $query = Leaderboard::with('user')
            ->where('game_id', $game->id);

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
    public function getUserRank(Game $game, int $userId, ?int $groupId = null): ?int
    {
        $leaderboard = Leaderboard::where('game_id', $game->id)
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
     * Get top performers for a game.
     */
    public function getTopPerformers(Game $game, int $limit = 10, ?int $groupId = null)
    {
        $query = Leaderboard::with('user')
            ->where('game_id', $game->id);

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
     * Get leaderboard statistics for a game.
     */
    public function getLeaderboardStats(Game $game, ?int $groupId = null): array
    {
        $query = Leaderboard::where('game_id', $game->id);

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
}
