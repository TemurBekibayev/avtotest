<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShablonAnswer extends Model
{
    protected $fillable = ['shablon_question_id', 'language', 'description', 'video_path'];
}
