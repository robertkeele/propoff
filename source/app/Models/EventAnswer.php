<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_question_id',
        'correct_answer',
        'is_void',
        'set_at',
        'set_by',
    ];

    protected $casts = [
        'is_void' => 'boolean',
        'set_at' => 'datetime',
    ];

    /**
     * Get the event that owns this answer.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the event question this answer is for.
     */
    public function eventQuestion()
    {
        return $this->belongsTo(EventQuestion::class);
    }

    /**
     * Get the admin user who set this answer.
     */
    public function setter()
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}
