<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShablonQuestion extends Model
{
    protected $fillable = ['json_id', 'image_path'];
    protected $appends = ['question_file', 'answer'];

    public function getQuestionFileAttribute()
    {
        return $this->image_path;
    }

    public function getAnswerAttribute()
    {
        $lang = request()->get('lang', 'uz');
        $answer = $this->answers()->where('language', $lang)->first();
        if (!$answer) $answer = $this->answers()->first();
        
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
