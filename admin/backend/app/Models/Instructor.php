<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instructor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'specialization',
        'image_path',
        'hired_date',
        'experience_years',
        'status',
        'category',
    ];
}
