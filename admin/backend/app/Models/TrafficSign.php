<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficSign extends Model
{
    protected $fillable = ['traffic_sign_category_id', 'title', 'description', 'image', 'content'];

    protected $casts = [
        'content' => 'array',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) return null;
        
        $path = $this->image;
        $path = ltrim($path, './');
        $path = str_replace(['https://api.amudaryoavtotest.uz/', 'http://api.amudaryoavtotest.uz/'], '', $path);
        
        if (!str_starts_with($path, 'storage/')) {
            $path = 'storage/' . $path;
        }

        return 'https://api.amudaryoavtotest.uz/' . str_replace(' ', '%20', $path);
    }

    public function category()
    {
        return $this->belongsTo(TrafficSignCategory::class, 'traffic_sign_category_id');
    }
}
