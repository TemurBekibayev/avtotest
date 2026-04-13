<?php
require __DIR__ . '/../admin/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../admin/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- test_templates (Old/Original) ---\n";
$old = DB::table('test_templates')->select('id', 'name')->limit(25)->get();
foreach($old as $t) echo "ID: {$t->id} | Name: {$t->name}\n";

echo "\n--- student_test_templates (New/Shablon) ---\n";
$new = DB::table('student_test_templates')->select('id', 'name')->limit(25)->get();
foreach($new as $t) echo "ID: {$t->id} | Name: {$t->name}\n";

echo "\n--- latest test_results ---\n";
$results = DB::table('test_results')->latest('id')->limit(10)->get();
foreach($results as $r) {
    echo "ID: {$r->id} | StudentID: {$r->student_id} | TestTemplID: " . ($r->test_template_id ?? 'NULL') . 
         " | StudentTemplID: " . ($r->student_test_template_id ?? 'NULL') . " | Score: {$r->score}\n";
}
