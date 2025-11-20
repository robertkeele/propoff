<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupQuestionAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'question_id',
        'correct_answer',
        'points_awarded',
        'is_void',
    ];

    protected $casts = [
        'is_void' => 'boolean',
    ];

    /**
     * Get the group that owns this answer.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the question that this answer is for.
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
