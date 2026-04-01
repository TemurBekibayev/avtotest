<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionTranslation extends Model
{
    protected $fillable = [
        'question_id',
        'language',
        'question',
    ];

    public function question()
    {
        return $this->belongsTo(TestQuestion::class , 'question_id');
    }
}
