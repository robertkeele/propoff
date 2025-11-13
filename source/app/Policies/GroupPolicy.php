<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GroupPolicy
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
    public function view(User $user, Group $group): bool
    {
        // Can view if public or if user is a member
        return $group->is_public || $group->users->contains($user->id);
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
    public function update(User $user, Group $group): bool
    {
        // User must be the creator
        return $user->id === $group->created_by;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Group $group): bool
    {
        // User must be the creator
        return $user->id === $group->created_by;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Group $group): bool
    {
        return $user->id === $group->created_by;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Group $group): bool
    {
        return $user->id === $group->created_by;
    }

    /**
     * Determine whether the user can add users to the group.
     */
    public function addUser(User $user, Group $group): bool
    {
        // User must be the creator or an admin
        return $user->id === $group->created_by || $user->role === 'admin';
    }

    /**
     * Determine whether the user can remove users from the group.
     */
    public function removeUser(User $user, Group $group): bool
    {
        // User must be the creator or an admin
        return $user->id === $group->created_by || $user->role === 'admin';
    }
}
