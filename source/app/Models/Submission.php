<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'group_id',
        'total_score',
        'possible_points',
        'percentage',
        'is_complete',
        'submitted_at',
    ];

    protected $casts = [
        'is_complete' => 'boolean',
        'submitted_at' => 'datetime',
        'percentage' => 'decimal:2',
    ];

    /**
     * Get the event that owns the submission.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user who made the submission.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the group this submission belongs to.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user answers for this submission.
     */
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }
}
