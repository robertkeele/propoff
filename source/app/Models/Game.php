<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'event_type',
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
     * Get the user who created the game.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the questions for the game.
     */
    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('display_order');
    }

    /**
     * Get the submissions for the game.
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Get the leaderboard entries for the game.
     */
    public function leaderboards()
    {
        return $this->hasMany(Leaderboard::class);
    }
}
