<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficSign extends Model
{
    protected $fillable = ['traffic_sign_type_id', 'title', 'image', 'content'];

    protected $guarded = [];

    protected $casts = [
        'content' => 'array',
    ];

    public function getImageAttribute($value)
    {
        if (!$value) return null;
        
        $path = $value;
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
