<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestOption extends Model
{
    protected $fillable = [
        'test_question_id',
        'option',
        'is_correct',
    ];

    public function question()
    {
        return $this->belongsTo(TestQuestion::class , 'test_question_id');
    }

    public function translations()
    {
        return $this->hasMany(OptionTranslation::class, 'option_id');
    }
}
