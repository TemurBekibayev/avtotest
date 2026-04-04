<?php
/**
 * 🛠️ cPanel Media & Path Repair Tool (FULL VERSION)
 * 
 * This script fixes 404 image errors by:
 * 1. Creating a storage symlink in public_html
 * 2. Repairing broken image paths in the MySQL database
 */

// 1. Initial Setup & Bootstrap Laravel
require __DIR__ . '/application/vendor/autoload.php';
$app = require_once __DIR__ . '/application/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<html><head><title>cPanel Shortcut & MySQL Path Repair</title><style>body{font-family:sans-serif; padding:40px; line-height:1.6; background-color:#f8fafc; color:#334155;} .container{max-width:800px; margin:0 auto; background:white; padding:30px; border-radius:12px; shadow:0 4px 6px -1px rgb(0 0 0 / 0.1); border:1px solid #e2e8f0;}</style></head><body><div class='container'>";
echo "<h2>🚀 Starting cPanel Shortcut & MySQL Repair...</h2><hr/>";

try {
    // 2. Create the Storage Shortcut (Symlink)
    // Target: /home/amudary1/application/storage/app/public
    // Link:   /home/amudary1/public_html/storage
    
    $targetPath = __DIR__ . '/application/storage/app/public';
    $shortcutPath = __DIR__ . '/storage';

    echo "<h3>1. Shortcut Creation</h3>";
    if (!file_exists($shortcutPath)) {
        if (symlink($targetPath, $shortcutPath)) {
            echo "<p style='color:green'>✅ SUCCESS: Storage shortcut created in <b>public_html/storage</b></p>";
        } else {
            // Backup method: try using shell if symlink is restricted
            $command = "ln -s " . escapeshellarg($targetPath) . " " . escapeshellarg($shortcutPath);
            @exec($command);
            if (file_exists($shortcutPath)) {
                echo "<p style='color:green'>✅ SUCCESS: Storage shortcut created via shell.</p>";
            } else {
                echo "<p style='color:red'>❌ ERROR: Could not create shortcut. Please check if your hosting allows symlinks or run 'php artisan storage:link' via terminal.</p>";
            }
        }
    } else {
        echo "<p style='color:blue'>ℹ️ INFO: Storage shortcut already exists.</p>";
    }

    // 3. Database Path Repair
    echo "<h3>2. MySQL Path Repair</h3>";
    $localDomains = ['http://localhost:8000', 'http://127.0.0.1:8000', 'http://localhost', 'http://127.0.0.1'];
    $fixedCount = 0;

    // Helper function for path fixing
    $fixPath = function($original) use ($localDomains, &$fixedCount) {
        if (!$original) return $original;
        $new = $original;

        // Strip local domains
        foreach ($localDomains as $domain) $new = str_replace($domain, '', $new);

        // Ensure starts with /storage/
        if (str_starts_with($new, 'storage/')) $new = '/' . $new;
        
        // Handle "images" -> "images changed" (for both traffic signs and questions)
        if (str_contains($new, '/storage/tests/images/') && !str_contains($new, 'images changed')) {
            $new = str_replace('/storage/tests/images/', '/storage/tests/images changed/', $new);
        }
        
        // Handle "videos" -> "videos changed" (for answers)
        if (str_contains($new, '/storage/tests/videos/') && !str_contains($new, 'videos changed')) {
            $new = str_replace('/storage/tests/videos/', '/storage/tests/videos changed/', $new);
        }

        if ($new !== $original) {
            $fixedCount++;
            return $new;
        }
        return $original;
    };

    // A. Road Sign Types (Categories)
    $types = \Illuminate\Support\Facades\DB::table('road_sign_types')->get();
    foreach ($types as $type) {
        $newPath = $fixPath($type->image_url);
        if ($newPath !== $type->image_url) {
            \Illuminate\Support\Facades\DB::table('road_sign_types')->where('id', $type->id)->update(['image_url' => $newPath]);
        }
    }

    // B. Traffic Signs (Individual Signs - image column)
    $signs = \Illuminate\Support\Facades\DB::table('traffic_signs')->get();
    foreach ($signs as $sign) {
        $newPath = $fixPath($sign->image);
        if ($newPath !== $sign->image) {
            \Illuminate\Support\Facades\DB::table('traffic_signs')->where('id', $sign->id)->update(['image' => $newPath]);
        }
        
        // Fix JSON Content (videos/extra images)
        if (!empty($sign->content)) {
            $originalContent = $sign->content;
            $newContent = $originalContent;
            
            if (str_contains($newContent, '/storage/tests/images/') && !str_contains($newContent, 'images changed')) {
                $newContent = str_replace('/storage/tests/images/', '/storage/tests/images changed/', $newContent);
            }
            if (str_contains($newContent, '/storage/tests/videos/') && !str_contains($newContent, 'videos changed')) {
                $newContent = str_replace('/storage/tests/videos/', '/storage/tests/videos changed/', $newContent);
            }
            
            if ($newContent !== $originalContent) {
                \Illuminate\Support\Facades\DB::table('traffic_signs')->where('id', $sign->id)->update(['content' => $newContent]);
                $fixedCount++;
            }
        }
    }

    // C. Test Questions (question_file column)
    $questions = \Illuminate\Support\Facades\DB::table('test_questions')->get();
    foreach ($questions as $q) {
        $newPath = $fixPath($q->question_file);
        if ($newPath !== $q->question_file) {
            \Illuminate\Support\Facades\DB::table('test_questions')->where('id', $q->id)->update(['question_file' => $newPath]);
        }
    }

    // D. Answers (answer_resource column)
    $answers = \Illuminate\Support\Facades\DB::table('answers')->get();
    foreach ($answers as $a) {
        $newPath = $fixPath($a->answer_resource);
        if ($newPath !== $a->answer_resource) {
            \Illuminate\Support\Facades\DB::table('answers')->where('id', $a->id)->update(['answer_resource' => $newPath]);
        }
    }

    echo "<p style='color:green'>✅ SUCCESS: Repaired <b>$fixedCount</b> total media entries in MySQL.</p>";

    echo "<hr/><h3>🎉 Final Result</h3>";
    echo "<p>Your media should now be visible in the student panel. Refresh your browser and check.</p>";
    echo "<p style='color:orange'>⚠️ IMPORTANT: Please delete this file (<b>repair_storage.php</b>) from your public_html folder immediately for security.</p>";

} catch (\Exception $e) {
    echo "<h3>❌ OOPS! An error occurred:</h3>";
    echo "<p style='color:red'>" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
