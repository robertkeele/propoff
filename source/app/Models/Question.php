<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
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
     * Get the game that owns the question.
     */
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the template that this question was created from.
     */
    public function template()
    {
        return $this->belongsTo(QuestionTemplate::class, 'template_id');
    }

    /**
     * Get the user answers for this question.
     */
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }

    /**
     * Get the group-specific answers for this question.
     */
    public function groupQuestionAnswers()
    {
        return $this->hasMany(GroupQuestionAnswer::class);
    }
}
