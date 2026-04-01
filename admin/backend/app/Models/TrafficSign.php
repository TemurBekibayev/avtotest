<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficSign extends Model
{
    protected $fillable = ['traffic_sign_category_id', 'title', 'description', 'image'];

    public function category()
    {
        return $this->belongsTo(TrafficSignCategory::class, 'traffic_sign_category_id');
    }
}
