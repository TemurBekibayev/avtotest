<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionTranslation extends Model
{
    protected $fillable = [
        'option_id',
        'language',
        'option',
    ];

    public function testOption()
    {
        return $this->belongsTo(TestOption::class, 'option_id');
    }
}
