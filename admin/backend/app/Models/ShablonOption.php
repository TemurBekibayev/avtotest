<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShablonOption extends Model
{
    protected $fillable = ['shablon_question_id', 'is_correct'];

    public function translations()
    {
        return $this->hasMany(ShablonOptionTranslation::class);
    }
}
