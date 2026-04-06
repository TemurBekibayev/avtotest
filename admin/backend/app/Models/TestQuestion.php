<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestQuestion extends Model
{
    protected $fillable = [
        'new_question_id',
        'question_file',
        'image',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) return null;
        
        $path = $this->image;
        if (!str_starts_with($path, 'storage/')) {
            $path = 'storage/' . $path;
        }

        return url($path);
    }

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
