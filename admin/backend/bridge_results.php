<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- 🚀 Starting Results Bridge Migration ---\n";

$affected = 0;
// We look for results that have an old 'test_template_id' but no 'student_test_template_id'
$results = DB::table('test_results')
    ->whereNotNull('test_template_id')
    ->where('test_template_id', '>=', 2)
    ->where('test_template_id', '<=', 21)
    ->whereNull('student_test_template_id')
    ->get();

foreach ($results as $res) {
    // Offset is -1 (e.g., Old ID 8 "7 SHABLON" -> New ID 7 "Shablon 7")
    $newId = $res->test_template_id - 1;
    
    // Verify the new template exists
    $templateExists = DB::table('student_test_templates')->where('id', $newId)->exists();
    
    if ($templateExists) {
        DB::table('test_results')->where('id', $res->id)->update([
            'student_test_template_id' => $newId
        ]);
        $affected++;
        echo "✅ Bridged Result ID {$res->id}: Old Target {$res->test_template_id} -> New Target {$newId}\n";
    } else {
        echo "⚠️ Skipping Result ID {$res->id}: New Template ID {$newId} not found.\n";
    }
}

echo "--- 🏁 Migration Complete. Bridged $affected results. ---\n";
