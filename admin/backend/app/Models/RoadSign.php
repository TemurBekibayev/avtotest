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

    public function getImageAttribute($value)
    {
        if (!$value) return null;
        
        // Clean the path (remove leading dots, slashes, or domains)
        $path = $value;
        $path = ltrim($path, './');
        $path = str_replace(['https://api.amudaryoavtotest.uz/', 'http://api.amudaryoavtotest.uz/'], '', $path);
        
        // Ensure it starts with storage/
        if (!str_starts_with($path, 'storage/')) {
            $path = 'storage/' . $path;
        }

        // Return a clean, encoded URL
        return 'https://api.amudaryoavtotest.uz/' . str_replace(' ', '%20', $path);
    }

    public function type()
    {
        return $this->belongsTo(RoadSignType::class, 'road_sign_type_id');
    }
}
