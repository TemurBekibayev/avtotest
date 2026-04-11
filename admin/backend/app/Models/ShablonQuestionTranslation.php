<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShablonQuestionTranslation extends Model
{
    protected $fillable = ['shablon_question_id', 'language', 'question_text'];
    protected $appends = ['question'];

    public function getQuestionAttribute()
    {
        return $this->question_text;
    }
}
