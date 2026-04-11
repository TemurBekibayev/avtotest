<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShablonOptionTranslation extends Model
{
    protected $fillable = ['shablon_option_id', 'language', 'option_text'];
    protected $appends = ['option'];

    public function getOptionAttribute()
    {
        return $this->option_text;
    }
}
