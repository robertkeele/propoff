<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubmissionPolicy
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
    public function view(User $user, Submission $submission): bool
    {
        // User can view their own submissions, or game creator can view all submissions for their game
        return $user->id === $submission->user_id
            || $user->id === $submission->game->created_by
            || $user->role === 'admin';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Submission $submission): bool
    {
        // User must own the submission
        if ($user->id !== $submission->user_id) {
            return false;
        }

        // Check if game is still before lock date
        if ($submission->game->lock_date && now()->isAfter($submission->game->lock_date)) {
            return false;
        }

        // Check if game is not completed or in progress
        if (in_array($submission->game->status, ['completed', 'in_progress'])) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Submission $submission): bool
    {
        // User can only delete their own incomplete submissions
        return $user->id === $submission->user_id && !$submission->is_complete;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Submission $submission): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Submission $submission): bool
    {
        return $user->role === 'admin';
    }
}
