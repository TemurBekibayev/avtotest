<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestResult extends Model
{
    protected $fillable = [
        'student_id',
        'test_template_id',
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
        return $this->belongsTo(TestTemplate::class , 'test_template_id');
    }
}
