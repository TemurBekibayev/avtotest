<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestResult extends Model
{
    protected $fillable = [
        'student_id',
        'test_template_id',
        'student_test_template_id',
        'score',
        'passed',
        'taken_at',
    ];

    protected $casts = [
        'passed' => 'boolean',
        'taken_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function template()
    {
        return $this->originalTemplate() ?? $this->shablonTemplate();
    }

    public function originalTemplate()
    {
        return $this->belongsTo(TestTemplate::class, 'test_template_id')->first();
    }

    public function shablonTemplate()
    {
        return $this->belongsTo(StudentTestTemplate::class, 'student_test_template_id')->first();
    }

    // This allows $result->template to work as an attribute in JSON/Frontend
    public function getTemplateAttribute()
    {
        return $this->originalTemplate() ?: $this->shablonTemplate();
    }
}
