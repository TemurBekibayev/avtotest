<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficSignCategory extends Model
{
    protected $fillable = ['name'];

    public function signs()
    {
        return $this->hasMany(TrafficSign::class);
    }
}
