<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Group;
use App\Models\Submission;
use App\Models\User;

class GameService
{
    /**
     * Check if a user has joined a game for a specific group.
     */
    public function hasUserJoinedGame(Game $game, User $user, Group $group): bool
    {
        return Submission::where('game_id', $game->id)
            ->where('user_id', $user->id)
            ->where('group_id', $group->id)
            ->exists();
    }

    /**
     * Get user's submission for a game and group.
     */
    public function getUserSubmission(Game $game, User $user, Group $group): ?Submission
    {
        return Submission::where('game_id', $game->id)
            ->where('user_id', $user->id)
            ->where('group_id', $group->id)
            ->first();
    }

    /**
     * Check if a game is playable (open and before lock date).
     */
    public function isGamePlayable(Game $game): bool
    {
        if ($game->status !== 'open') {
            return false;
        }

        if ($game->lock_date && now()->isAfter($game->lock_date)) {
            return false;
        }

        return true;
    }

    /**
     * Get active games for a user.
     */
    public function getActiveGames(int $limit = 10)
    {
        return Game::where('status', 'open')
            ->where('lock_date', '>', now())
            ->orderBy('lock_date', 'asc')
            ->with('creator')
            ->withCount('questions')
            ->limit($limit)
            ->get();
    }

    /**
     * Get completed games.
     */
    public function getCompletedGames(int $limit = 10)
    {
        return Game::where('status', 'completed')
            ->orderBy('event_date', 'desc')
            ->with('creator')
            ->withCount(['questions', 'submissions'])
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate total possible points for a game.
     */
    public function calculatePossiblePoints(Game $game): int
    {
        return $game->questions()->sum('points');
    }

    /**
     * Get games user can participate in (open and before lock).
     */
    public function getAvailableGamesForUser(User $user, int $perPage = 15)
    {
        return Game::where('status', 'open')
            ->where(function ($query) {
                $query->whereNull('lock_date')
                    ->orWhere('lock_date', '>', now());
            })
            ->with('creator')
            ->withCount('questions')
            ->orderBy('event_date', 'asc')
            ->paginate($perPage);
    }

    /**
     * Search games by name or category.
     */
    public function searchGames(string $query, int $perPage = 15)
    {
        return Game::where('name', 'like', "%{$query}%")
            ->orWhere('category', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->with('creator')
            ->withCount(['questions', 'submissions'])
            ->orderBy('event_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Filter games by status.
     */
    public function filterGamesByStatus(string $status, int $perPage = 15)
    {
        return Game::where('status', $status)
            ->with('creator')
            ->withCount(['questions', 'submissions'])
            ->orderBy('event_date', 'desc')
            ->paginate($perPage);
    }
}
