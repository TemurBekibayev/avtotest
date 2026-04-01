<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestQuestion extends Model
{
    protected $fillable = [
        'new_question_id',
        'question_file',
    ];

    public function options()
    {
        return $this->hasMany(TestOption::class);
    }

    public function translations()
    {
        return $this->hasMany(QuestionTranslation::class , 'question_id');
    }

    public function answer()
    {
        return $this->hasOne(Answer::class , 'test_question_id');
    }
}
