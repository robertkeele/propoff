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
