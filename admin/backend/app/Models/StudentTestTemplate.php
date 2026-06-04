<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentTestTemplate extends Model
{
    protected $table = 'student_test_templates';

    protected $fillable = [
        'name',
        'type',
        'duration_minutes',
        'passing_score',
    ];

    protected $appends = ['question_count', 'time_limit', 'description', 'latest_result'];

    public function getLatestResultAttribute()
    {
        return $this->attributes['latest_result'] ?? null;
    }

    public function getQuestionCountAttribute()
    {
        if (array_key_exists('questions_count', $this->attributes)) {
            return $this->attributes['questions_count'] ?: 20;
        }
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
        return $this->belongsToMany(ShablonQuestion::class, 'student_template_questions', 'template_id', 'question_id');
    }

    public function results()
    {
        return $this->hasMany(TestResult::class, 'student_test_template_id');
    }

    public function latestResult()
    {
        return $this->hasOne(TestResult::class, 'student_test_template_id')->latestOfMany();
    }
}
