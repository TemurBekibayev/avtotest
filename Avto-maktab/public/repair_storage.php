<?php
/**
 * 🛠️ cPanel Media & Path Repair Tool (V3 - Final)
 * 
 * This script fixes 404 image errors by:
 * 1. Creating a storage symlink in application/public (the root of api.amudaryoavtotest.uz)
 * 2. Repairing broken image paths in the MySQL database
 */

// 1. Initial Setup & Bootstrap Laravel
// Note: We are assuming this script is uploaded to public_html/
require __DIR__ . '/application/vendor/autoload.php';
$app = require_once __DIR__ . '/application/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<html><head><title>Final MySQL Path Repair</title><style>body{font-family:sans-serif; padding:40px; line-height:1.6; background-color:#f8fafc; color:#334155;} .container{max-width:800px; margin:0 auto; background:white; padding:30px; border-radius:12px; shadow:0 4px 6px -1px rgb(0 0 0 / 0.1); border:1px solid #e2e8f0;}</style></head><body><div class='container'>";
echo "<h2>🚀 Starting Media & Database Repair...</h2><hr/>";

try {
    // 2. Create the Storage Shortcut (Symlink)
    // Target: application/storage/app/public
    // Link:   application/public/storage (Root of API domain)
    
    $storageTarget = __DIR__ . '/application/storage/app/public';
    $apiPublicStorage = __DIR__ . '/application/public/storage';
    $webPublicStorage = __DIR__ . '/storage'; // public_html/storage

    echo "<h3>1. Shortcut Creation</h3>";
    
    // Create link for API subdomain
    if (!file_exists($apiPublicStorage)) {
        if (symlink($storageTarget, $apiPublicStorage)) {
            echo "<p style='color:green'>✅ SUCCESS: Storage shortcut created in <b>application/public/storage</b></p>";
        } else {
            $command = "ln -s " . escapeshellarg($storageTarget) . " " . escapeshellarg($apiPublicStorage);
            @exec($command);
            echo file_exists($apiPublicStorage) 
                ? "<p style='color:green'>✅ SUCCESS: Storage shortcut created via shell in API folder.</p>" 
                : "<p style='color:red'>❌ ERROR: Could not create shortcut in API folder. Check permissions.</p>";
        }
    } else {
        echo "<p style='color:blue'>ℹ️ INFO: Storage shortcut already exists in application/public/storage.</p>";
    }

    // Create link for Main domain (optional but good for relative paths)
    if (!file_exists($webPublicStorage)) {
        symlink($storageTarget, $webPublicStorage);
    }

    // 3. Database Path Repair
    echo "<h3>2. MySQL Path Repair</h3>";
    $localDomains = ['http://localhost:8000', 'http://127.0.0.1:8000', 'http://localhost', 'http://127.0.0.1'];
    $fixedCount = 0;

    $fixPath = function($original) use ($localDomains, &$fixedCount) {
        if (!$original) return $original;
        $new = $original;

        foreach ($localDomains as $domain) $new = str_replace($domain, '', $new);
        if (str_starts_with($new, 'storage/')) $new = '/' . $new;
        
        // Ensure "images changed" is used
        if (str_contains($new, '/storage/tests/images/') && !str_contains($new, 'images changed')) {
            $new = str_replace('/storage/tests/images/', '/storage/tests/images changed/', $new);
        }
        if (str_contains($new, '/storage/tests/videos/') && !str_contains($new, 'videos changed')) {
            $new = str_replace('/storage/tests/videos/', '/storage/tests/videos changed/', $new);
        }

        if ($new !== $original) {
            $fixedCount++;
            return $new;
        }
        return $original;
    };

    // Repair all tables
    $tables = [
        'road_sign_types' => 'image_url',
        'traffic_signs' => 'image',
        'test_questions' => 'question_file',
        'answers' => 'answer_resource'
    ];

    foreach ($tables as $table => $column) {
        $records = \Illuminate\Support\Facades\DB::table($table)->get();
        foreach ($records as $record) {
            $newPath = $fixPath($record->$column);
            if ($newPath !== $record->$column) {
                \Illuminate\Support\Facades\DB::table($table)->where('id', $record->id)->update([$column => $newPath]);
            }
        }
        
        // Specific fix for traffic_signs content JSON
        if ($table === 'traffic_signs') {
            foreach ($records as $record) {
                if (!empty($record->content)) {
                    $original = $record->content;
                    $new = $original;
                    if (str_contains($new, '/storage/tests/images/') && !str_contains($new, 'images changed')) {
                        $new = str_replace('/storage/tests/images/', '/storage/tests/images changed/', $new);
                    }
                    if ($new !== $original) {
                        \Illuminate\Support\Facades\DB::table('traffic_signs')->where('id', $record->id)->update(['content' => $new]);
                        $fixedCount++;
                    }
                }
            }
        }
    }

    echo "<p style='color:green'>✅ SUCCESS: Repaired <b>$fixedCount</b> total media entries in MySQL.</p>";

    echo "<hr/><h3>🎉 Final Result</h3>";
    echo "<p>Your media should now be visible. Refresh your browser.</p>";
    echo "<p style='color:orange'>⚠️ IMPORTANT: Delete <b>repair_storage.php</b> from cPanel now.</p>";

} catch (\Exception $e) {
    echo "<h3>❌ OOPS! An error occurred:</h3>";
    echo "<p style='color:red'>" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
