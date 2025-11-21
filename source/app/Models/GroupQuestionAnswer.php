<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupQuestionAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'group_question_id',
        'correct_answer',
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
     * Get the group question that this answer is for.
     */
    public function groupQuestion()
    {
        return $this->belongsTo(GroupQuestion::class);
    }
}
