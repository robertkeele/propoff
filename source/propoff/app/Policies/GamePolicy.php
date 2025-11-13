<?php

namespace App\Policies;

use App\Models\Game;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GamePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Game $game): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admins can create games
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Game $game): bool
    {
        // User must be the creator or an admin
        return $user->id === $game->created_by || $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Game $game): bool
    {
        // User must be the creator or an admin
        return $user->id === $game->created_by || $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Game $game): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Game $game): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can submit answers to the game.
     */
    public function submit(User $user, Game $game): bool
    {
        // Game must be open for submissions
        if ($game->status !== 'open') {
            return false;
        }

        // Game must not be past the lock date
        if ($game->lock_date && now()->isAfter($game->lock_date)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can view the game results.
     */
    public function viewResults(User $user, Game $game): bool
    {
        // Results are visible when game is completed or admin at any time
        return $game->status === 'completed' || $user->role === 'admin';
    }
}
