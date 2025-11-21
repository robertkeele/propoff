<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'guest_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Check if user is a guest.
     */
    public function isGuest()
    {
        return $this->role === 'guest';
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can edit a submission.
     */
    public function canEditSubmission(Submission $submission)
    {
        // User must own the submission
        if ($this->id !== $submission->user_id) {
            return false;
        }

        // Cannot edit if event is locked
        if ($submission->event->lock_date && now()->greaterThan($submission->event->lock_date)) {
            return false;
        }

        // Cannot edit if event is completed
        if ($submission->event->status === 'completed') {
            return false;
        }

        return true;
    }

    /**
     * Get the groups that the user belongs to.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'user_groups')
                    ->withTimestamps()
                    ->withPivot('joined_at', 'is_captain');
    }

    /**
     * Get the groups where the user is a captain.
     */
    public function captainOf()
    {
        return $this->belongsToMany(Group::class, 'user_groups')
                    ->wherePivot('is_captain', true)
                    ->withTimestamps()
                    ->withPivot('joined_at', 'is_captain');
    }

    /**
     * Get the groups created by the user.
     */
    public function createdGroups()
    {
        return $this->hasMany(Group::class, 'created_by');
    }

    /**
     * Get the events created by the user.
     */
    public function createdEvents()
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    /**
     * Get the question templates created by the user.
     */
    public function createdQuestionTemplates()
    {
        return $this->hasMany(QuestionTemplate::class, 'created_by');
    }

    /**
     * Get the submissions for the user.
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Get the leaderboard entries for the user.
     */
    public function leaderboards()
    {
        return $this->hasMany(Leaderboard::class);
    }

    /**
     * Check if user is a captain of a specific group.
     */
    public function isCaptainOf($group)
    {
        if (!$group) {
            return false;
        }

        return $this->captainOf()->where('group_id', $group->id)->exists();
    }

    /**
     * Check if user is a captain of any group.
     */
    public function isCaptain()
    {
        return $this->captainOf()->exists();
    }

    /**
     * Get all groups where this user is a captain.
     */
    public function getCaptainGroups()
    {
        return $this->captainOf;
    }
}
