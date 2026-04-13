<?php
// admin/backend/public/migrate.php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

echo "<pre>";
echo "🚀 Running database migrations (adding missing columns)...\n";

try {
    // Only run the migrations, which is very lightweight
    $exitCode = Artisan::call('migrate', ['--force' => true]);
    echo "Artisan Output:\n" . Artisan::output() . "\n";
    
    if ($exitCode === 0) {
        echo "✅ Migrations completed successfully!\n";
        echo "You can now run the results bridge script at /bridge_results.php";
    } else {
        echo "⚠️ Migration finished with code: $exitCode. Check output above.\n";
    }
} catch (\Exception $e) {
    echo "❌ Error during migration: " . $e->getMessage() . "\n";
}

echo "</pre>";
