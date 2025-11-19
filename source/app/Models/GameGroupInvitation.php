<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GameGroupInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'group_id',
        'token',
        'max_uses',
        'times_used',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the game that owns the invitation.
     */
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the group that owns the invitation.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Generate a unique token for invitation.
     */
    public static function generateToken()
    {
        return Str::random(32);
    }

    /**
     * Check if invitation is valid.
     */
    public function isValid()
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses && $this->times_used >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage()
    {
        $this->increment('times_used');
    }

    /**
     * Get full invitation URL.
     */
    public function getUrl()
    {
        return route('guest.join', ['token' => $this->token]);
    }
}
