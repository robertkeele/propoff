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
     * Get the users that belong to the group.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_groups')
                    ->withTimestamps()
                    ->withPivot('joined_at');
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
        return $this->hasMany(GameGroupInvitation::class);
    }
}
