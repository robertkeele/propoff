<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventQuestion extends Model
{
    use HasFactory;

    protected $table = 'event_questions';

    protected $fillable = [
        'event_id',
        'template_id',
        'question_text',
        'question_type',
        'options',
        'points',
        'display_order',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    protected $appends = [
        'type',
        'order_number',
    ];

    /**
     * Get the question type (accessor for question_type).
     */
    public function getTypeAttribute()
    {
        return $this->question_type;
    }

    /**
     * Get the order number (accessor for display_order).
     */
    public function getOrderNumberAttribute()
    {
        return $this->display_order;
    }

    /**
     * Get the event that owns the question.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the template that this question was created from.
     */
    public function template()
    {
        return $this->belongsTo(QuestionTemplate::class, 'template_id');
    }

    /**
     * Get the group questions created from this event question.
     */
    public function groupQuestions()
    {
        return $this->hasMany(GroupQuestion::class);
    }

    /**
     * Get the event answer (admin-set correct answer) for this question.
     */
    public function eventAnswer()
    {
        return $this->hasOne(EventAnswer::class);
    }

    /**
     * Get the event answers (admin-set correct answers) for this question.
     * This is typically one answer, but using hasMany for flexibility.
     */
    public function eventAnswers()
    {
        return $this->hasMany(EventAnswer::class);
    }
}
