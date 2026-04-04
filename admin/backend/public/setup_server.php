<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ini_set('max_execution_time', 300); // Allow time for intense migrations/seeds

echo "<html><head><title>cPanel Database & Server Setup</title><style>body{font-family:sans-serif; padding:40px; line-height:1.6;}</style></head><body>";
echo "<h2>🚀 Starting Full Automatic cPanel Setup...</h2><hr/>";

try {
    // 0. Ensure .env file exists and has an APP_KEY
    $envPath = __DIR__ . '/../.env';
    $envExamplePath = __DIR__ . '/../.env.example';

    if (!file_exists($envPath)) {
        if (file_exists($envExamplePath)) {
            copy($envExamplePath, $envPath);
            echo "<p>✅ Created .env from .env.example.</p>";
        } else {
            echo "<p>❌ Error: .env.example not found. Cannot create .env.</p>";
        }
    }

    if (file_exists($envPath)) {
        $content = file_get_contents($envPath);
        if (strpos($content, 'APP_KEY=') === false || empty(trim(explode("\n", explode('APP_KEY=', $content)[1])[0]))) {
            \Illuminate\Support\Facades\Artisan::call('key:generate', ['--force' => true]);
            echo "<p>✅ Application Encryption Key (APP_KEY) generated successfully.</p>";
        } else {
            echo "<p>✅ Application Encryption Key (APP_KEY) already exists.</p>";
        }
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

    // 4. Fix Media Paths (Repair hardcoded local paths and directories)
    $localDomains = ['http://localhost:8000', 'http://127.0.0.1:8000', 'http://localhost', 'http://127.0.0.1'];
    
    // Fix Questions
    $qCount = 0;
    $questions = \Illuminate\Support\Facades\DB::table('test_questions')->whereNotNull('question_file')->get();
    foreach ($questions as $q) {
        $original = $q->question_file;
        $new = $original;
        foreach ($localDomains as $domain) $new = str_replace($domain, '', $new);
        if (str_contains($new, '/storage/tests/images/') && !str_contains($new, 'images changed')) {
            $new = str_replace('/storage/tests/images/', '/storage/tests/images changed/', $new);
        }
        if (!empty($new) && !str_starts_with($new, '/storage/')) {
            if (str_starts_with($new, 'storage/')) $new = '/' . $new;
            else if (str_starts_with($new, 'tests/')) $new = '/storage/' . $new;
        }
        if ($new !== $original) {
            \Illuminate\Support\Facades\DB::table('test_questions')->where('id', $q->id)->update(['question_file' => $new]);
            $qCount++;
        }
    }
    
    // Fix Answers
    $aCount = 0;
    $answers = \Illuminate\Support\Facades\DB::table('answers')->whereNotNull('answer_resource')->get();
    foreach ($answers as $a) {
        $original = $a->answer_resource;
        $new = $original;
        foreach ($localDomains as $domain) $new = str_replace($domain, '', $new);
        if (str_contains($new, '/storage/tests/videos/') && !str_contains($new, 'videos changed')) {
            $new = str_replace('/storage/tests/videos/', '/storage/tests/videos changed/', $new);
        }
        if (!empty($new) && !str_starts_with($new, '/storage/')) {
            if (str_starts_with($new, 'storage/')) $new = '/' . $new;
            else if (str_starts_with($new, 'tests/')) $new = '/storage/' . $new;
        }
        if ($new !== $original) {
            \Illuminate\Support\Facades\DB::table('answers')->where('id', $a->id)->update(['answer_resource' => $new]);
            $aCount++;
        }
    }

    // Fix Traffic Signs
    $tsCount = 0;
    $trafficSigns = \Illuminate\Support\Facades\DB::table('traffic_signs')->get();
    foreach ($trafficSigns as $ts) {
        $updated = false;
        
        // Fix main image
        if (!empty($ts->image)) {
            $original = $ts->image;
            $new = $original;
            foreach ($localDomains as $domain) $new = str_replace($domain, '', $new);
            if (str_contains($new, '/storage/tests/images/') && !str_contains($new, 'images changed')) {
                $new = str_replace('/storage/tests/images/', '/storage/tests/images changed/', $new);
            }
            if (!empty($new) && !str_starts_with($new, '/storage/')) {
                if (str_starts_with($new, 'storage/')) $new = '/' . $new;
                else if (str_starts_with($new, 'tests/')) $new = '/storage/' . $new;
            }
            if ($new !== $original) {
                \Illuminate\Support\Facades\DB::table('traffic_signs')->where('id', $ts->id)->update(['image' => $new]);
                $updated = true;
            }
        }

        // Fix content JSON (videos and extra images)
        if (!empty($ts->content)) {
            $originalContent = $ts->content;
            $newContent = $originalContent;
            // The content might be double encoded or a string, let's just do string replacement
            if (str_contains($newContent, '/storage/tests/images/') && !str_contains($newContent, 'images changed')) {
                $newContent = str_replace('/storage/tests/images/', '/storage/tests/images changed/', $newContent);
                if ($newContent !== $originalContent) {
                    \Illuminate\Support\Facades\DB::table('traffic_signs')->where('id', $ts->id)->update(['content' => $newContent]);
                    $updated = true;
                }
            }
        }

        if ($updated) $tsCount++;
    }
    echo "<p>✅ Analyzed and repaired media paths ($qCount questions, $aCount answers, $tsCount signs fixed).</p>";

    echo "<hr/><h2>🎉 Awesome! Everything is perfectly setup!</h2>";
    echo "<p style='color:green;font-weight:bold'>Your website and backend database is completely ready to use.</p>";
    echo "<p>Note: For security, you should delete this 'setup_server.php' file or rename it after using.</p>";
} catch (\Exception $e) {
    echo "<h2>❌ Oops! An Error Occurred:</h2>";
    echo "<p style='color:red'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</body></html>";
