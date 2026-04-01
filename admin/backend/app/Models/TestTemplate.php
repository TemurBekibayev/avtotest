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

    public function questions()
    {
        // Assuming a many-to-many relationship using pivot table template_questions
        return $this->belongsToMany(TestQuestion::class , 'template_questions');
    }
}
