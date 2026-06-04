<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShablonOption extends Model
{
    protected $fillable = ['shablon_question_id', 'is_correct'];
    protected $appends = ['option'];

    public function getOptionAttribute()
    {
        $lang = request()->get('lang', 'uz-lat');
        $translation = $this->translations->where('language', $lang)->first();
        if (!$translation) $translation = $this->translations->first();
        return $translation ? $translation->option_text : '';
    }

    public function translations()
    {
        return $this->hasMany(ShablonOptionTranslation::class);
    }
}
