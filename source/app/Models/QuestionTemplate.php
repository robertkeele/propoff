<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'question_text',
        'question_type',
        'category',
        'default_points',
        'variables',
        'default_options',
        'is_favorite',
        'created_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'default_options' => 'array',
        'is_favorite' => 'boolean',
    ];

    /**
     * Get the user who created the template.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the questions created from this template.
     */
    public function questions()
    {
        return $this->hasMany(Question::class, 'template_id');
    }
}
