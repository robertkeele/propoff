<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CaptainInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'token',
        'max_uses',
        'times_used',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'times_used' => 'integer',
        'max_uses' => 'integer',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the event this invitation is for.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user who created this invitation.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate a unique token for the invitation.
     */
    public static function generateToken()
    {
        return Str::random(32);
    }

    /**
     * Get the full invitation URL.
     */
    public function getUrlAttribute()
    {
        return url('/captain/join/' . $this->token);
    }

    /**
     * Check if the invitation is still valid.
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
     * Increment the usage counter.
     */
    public function incrementUsage()
    {
        $this->increment('times_used');
    }

    /**
     * Scope to get only active invitations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only valid invitations (active, not expired, under max uses).
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
