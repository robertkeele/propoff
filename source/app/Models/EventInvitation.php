<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EventInvitation extends Model
{
    use HasFactory;

    protected $table = 'event_invitations';

    protected $fillable = [
        'event_id',
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
        'times_used' => 'integer',
        'max_uses' => 'integer',
    ];

    /**
     * Get the event that owns the invitation.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
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

    /**
     * Scope to get only active invitations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only valid invitations.
     */
    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereRaw('times_used < max_uses');
            });
    }
}
