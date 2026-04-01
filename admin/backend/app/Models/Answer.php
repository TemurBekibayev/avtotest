<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = [
        'test_question_id',
        'answer_description',
        'answer_resource',
    ];

    public function question()
    {
        return $this->belongsTo(TestQuestion::class , 'test_question_id');
    }
}
