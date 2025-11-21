<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'event_question_id',
        'question_text',
        'question_type',
        'options',
        'points',
        'display_order',
        'is_active',
        'is_custom',
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
    ];

    /**
     * Get the group that owns this question.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the event question this was created from (null if custom).
     */
    public function eventQuestion()
    {
        return $this->belongsTo(EventQuestion::class, 'event_question_id');
    }

    /**
     * Get the user answers for this group question.
     */
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }

    /**
     * Get the captain-set correct answer for this group question.
     */
    public function groupQuestionAnswer()
    {
        return $this->hasOne(GroupQuestionAnswer::class);
    }

    /**
     * Scope to get only active questions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only custom questions.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_custom', true);
    }

    /**
     * Scope to get only standard (non-custom) questions.
     */
    public function scopeStandard($query)
    {
        return $query->where('is_custom', false);
    }
}
