<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ini_set('max_execution_time', 300); // Allow time for intense migrations/seeds

echo "<html><head><title>cPanel Database & Server Setup</title><style>body{font-family:sans-serif; padding:40px; line-height:1.6;}</style></head><body>";
echo "<h2>🚀 Starting Full Automatic cPanel Setup...</h2><hr/>";

try {
    // 0. Ensure APP_KEY exists
    if (!env('APP_KEY')) {
        \Illuminate\Support\Facades\Artisan::call('key:generate', ['--force' => true]);
        echo "<p>✅ Application Encryption Key (APP_KEY) generated and saved to .env.</p>";
    }
    // 1. Create Storage Link (Shortcut for Media files)
    $target = __DIR__ . '/../storage/app/public';
    $link = __DIR__ . '/storage';
    if (!file_exists($link)) {
        if(symlink($target, $link)){
            echo "<p>✅ Storage Symlink created. Images and Videos are now online.</p>";
        } else {
            echo "<p>⚠️ Could not create Storage Symlink. Check permissions.</p>";
        }
    } else {
        echo "<p>✅ Storage Symlink already exists.</p>";
    }

    // 2. Run fresh migrations
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo "<p>✅ Migrations (Database Tables structure) created successfully.</p>";

    // 3. Complete Seeding Process
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
    echo "<p>✅ Default Users, Administrator, and Groups are ready.</p>";

    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'TestTranslationSeeder', '--force' => true]);
    echo "<p>✅ Test Translation JSON files deeply imported.</p>";

    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'RoadSignSeeder', '--force' => true]);
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'TrafficSignsImportSeeder', '--force' => true]);
    echo "<p>✅ Road and Traffic signs catalogs successfully established.</p>";

    echo "<hr/><h2>🎉 Awesome! Everything is perfectly setup!</h2>";
    echo "<p style='color:green;font-weight:bold'>Your website and backend database is completely ready to use.</p>";
    echo "<p>Note: For security, you should delete this 'setup_server.php' file or rename it after using.</p>";
} catch (\Exception $e) {
    echo "<h2>❌ Oops! An Error Occurred:</h2>";
    echo "<p style='color:red'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</body></html>";
