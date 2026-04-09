<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestTemplate extends Model
{
    protected $fillable = [
        'name',
        'type',
        'duration_minutes',
        'passing_score',
    ];

    protected $appends = ['question_count', 'time_limit', 'description'];

    public function getQuestionCountAttribute()
    {
        return $this->questions()->count() ?: 20;
    }

    public function getTimeLimitAttribute()
    {
        return $this->duration_minutes ?: 15;
    }

    public function getDescriptionAttribute()
    {
        return $this->type ?: '';
    }

    public function questions()
    {
        // Assuming a many-to-many relationship using pivot table template_questions
        return $this->belongsToMany(TestQuestion::class , 'template_questions');
    }
}
