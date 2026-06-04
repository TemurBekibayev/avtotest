<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShablonQuestion extends Model
{
    protected $fillable = ['json_id', 'image_path'];
    protected $appends = ['question', 'question_file', 'answer'];

    public function getQuestionAttribute()
    {
        $lang = request()->get('lang', 'uz-lat');
        $translation = $this->translations->where('language', $lang)->first();
        if (!$translation) $translation = $this->translations->first();
        return $translation ? $translation->question_text : '';
    }

    public function getQuestionFileAttribute()
    {
        if ($this->image_path && str_contains($this->image_path, '/test_files/img/newtest_questions/')) {
            return str_replace('/test_files/img/newtest_questions/', '/storage/tests/extra-images/', $this->image_path);
        }
        return $this->image_path;
    }

    public function getAnswerAttribute()
    {
        $lang = request()->get('lang', 'uz-lat');
        $answer = $this->answers->where('language', $lang)->first();
        if (!$answer) $answer = $this->answers->first();
        
        if ($answer) {
            return [
                'answer_description' => $answer->description,
                'answer_resource' => $answer->video_path
            ];
        }
        return null;
    }

    public function translations()
    {
        return $this->hasMany(ShablonQuestionTranslation::class);
    }

    public function options()
    {
        return $this->hasMany(ShablonOption::class);
    }

    public function answers()
    {
        return $this->hasMany(ShablonAnswer::class);
    }
}
