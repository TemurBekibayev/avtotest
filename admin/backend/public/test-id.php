<?php
// Identity Test - Visit this at api.amudaryoavtotest.uz/test-id.php

// 1. Check Server Variables first
echo "<h3>1. Server Variables</h3>";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET') . "<br>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "<br>";

// 2. Try to read .env file directly (The "Secret" Source)
echo "<h3>2. Raw .env File Check</h3>";
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    preg_match('/APP_URL=(.*)/', $envContent, $matches);
    echo "APP_URL line in .env file: <strong>" . ($matches[0] ?? 'NOT FOUND') . "</strong><br>";
} else {
    echo ".env file not found at $envPath<br>";
}

// 3. Bootstrap Laravel to see the "Memory"
echo "<h3>3. Laravel Memory Check</h3>";
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    echo "APP_URL in memory: <strong>" . env('APP_URL') . "</strong><br>";
    echo "Config URL: <strong>" . config('app.url') . "</strong><br>";
    echo "URL Generator: <strong>" . url('/') . "</strong><br>";
    
    // 4. Check Database Content
    echo "<h3>4. Database Content Check</h3>";
    $firstSign = \App\Models\RoadSign::whereNotNull('image')->first();
    if ($firstSign) {
        echo "Raw 'image' in DB: <strong>" . $firstSign->image . "</strong><br>";
        echo "Generated 'image_url': <strong>" . $firstSign->image_url . "</strong><br>";
    } else {
        echo "No road signs found in database.<br>";
    }

} catch (\Exception $e) {
    echo "Error loading Laravel: " . $e->getMessage();
}
