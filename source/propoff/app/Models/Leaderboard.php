<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'group_id',
        'user_id',
        'rank',
        'total_score',
        'possible_points',
        'percentage',
        'answered_count',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    /**
     * Get the game that owns the leaderboard entry.
     */
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the group for this leaderboard entry (nullable for global leaderboard).
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user for this leaderboard entry.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
