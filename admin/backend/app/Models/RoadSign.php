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

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) return null;
        
        // Ensure the path starts with storage/
        $path = $this->image;
        if (!str_starts_with($path, 'storage/')) {
            $path = 'storage/' . $path;
        }

        return url($path);
    }

    public function type()
    {
        return $this->belongsTo(RoadSignType::class, 'road_sign_type_id');
    }
}
