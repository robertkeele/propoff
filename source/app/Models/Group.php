<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_public',
        'event_id',
        'grading_source',
        'created_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get the user who created the group.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the event this group is participating in.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the users that belong to the group.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_groups')
                    ->withTimestamps()
                    ->withPivot('joined_at', 'is_captain');
    }

    /**
     * Alias for users() - Get the members of this group.
     */
    public function members()
    {
        return $this->users();
    }

    /**
     * Get the captains of this group.
     */
    public function captains()
    {
        return $this->belongsToMany(User::class, 'user_groups')
                    ->wherePivot('is_captain', true)
                    ->withTimestamps()
                    ->withPivot('joined_at', 'is_captain');
    }

    /**
     * Get the submissions for the group.
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Get the leaderboard entries for the group.
     */
    public function leaderboards()
    {
        return $this->hasMany(Leaderboard::class);
    }

    /**
     * Get the group questions for this group.
     */
    public function groupQuestions()
    {
        return $this->hasMany(GroupQuestion::class)->orderBy('display_order');
    }

    /**
     * Get the group-specific question answers for this group.
     */
    public function groupQuestionAnswers()
    {
        return $this->hasMany(GroupQuestionAnswer::class);
    }

    /**
     * Get the invitations for the group.
     */
    public function invitations()
    {
        return $this->hasMany(EventInvitation::class);
    }

    /**
     * Check if a user is a captain of this group.
     */
    public function isCaptain($user)
    {
        if (!$user) {
            return false;
        }

        // Handle both User objects and user IDs
        $userId = is_object($user) ? $user->id : $user;

        return $this->users()
            ->wherePivot('is_captain', true)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Make a user a captain of this group.
     */
    public function addCaptain($user)
    {
        // Handle both User objects and user IDs
        $userId = is_object($user) ? $user->id : $user;

        if (!$this->users->contains($userId)) {
            $this->users()->attach($userId, [
                'joined_at' => now(),
                'is_captain' => true,
            ]);
        } else {
            $this->users()->updateExistingPivot($userId, [
                'is_captain' => true,
            ]);
        }
    }

    /**
     * Remove captain status from a user.
     */
    public function removeCaptain($user)
    {
        // Handle both User objects and user IDs
        $userId = is_object($user) ? $user->id : $user;

        $this->users()->updateExistingPivot($userId, [
            'is_captain' => false,
        ]);
    }

    /**
     * Check if this group uses captain grading.
     */
    public function usesCaptainGrading()
    {
        return $this->grading_source === 'captain';
    }

    /**
     * Check if this group uses admin grading.
     */
    public function usesAdminGrading()
    {
        return $this->grading_source === 'admin';
    }
}
