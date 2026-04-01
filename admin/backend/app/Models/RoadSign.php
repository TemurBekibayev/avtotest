<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoadSign extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'name' => 'array',
        'content' => 'array',
    ];

    public function type()
    {
        return $this->belongsTo(RoadSignType::class, 'road_sign_type_id');
    }
}
