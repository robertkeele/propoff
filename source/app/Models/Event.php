<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'name',
        'description',
        'category',
        'event_date',
        'status',
        'lock_date',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'lock_date' => 'datetime',
    ];

    /**
     * Get the user who created the event.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the event questions for the event.
     */
    public function eventQuestions()
    {
        return $this->hasMany(EventQuestion::class)->orderBy('order');
    }

    /**
     * Get the groups participating in this event.
     */
    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Get the submissions for the event.
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Get the leaderboard entries for the event.
     */
    public function leaderboards()
    {
        return $this->hasMany(Leaderboard::class);
    }

    /**
     * Get the invitations for the event.
     */
    public function invitations()
    {
        return $this->hasMany(EventInvitation::class);
    }

    /**
     * Get the event answers (admin-set correct answers).
     */
    public function eventAnswers()
    {
        return $this->hasMany(EventAnswer::class);
    }

    /**
     * Get all available question templates for this event's category.
     * Returns templates that match this event's category.
     */
    public function availableTemplates()
    {
        return QuestionTemplate::where('category', $this->category)
            ->orderBy('is_favorite', 'desc')
            ->orderBy('title', 'asc')
            ->get();
    }
}
