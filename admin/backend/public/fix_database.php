<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<h2>Database Fix Tool</h2>";

try {
    echo "Checking 'test_results' table...<br>";
    
    if (!Schema::hasColumn('test_results', 'student_test_template_id')) {
        echo "<b>Action:</b> Adding 'student_test_template_id' column...<br>";
        
        Schema::table('test_results', function (Blueprint $table) {
            $table->unsignedBigInteger('student_test_template_id')->nullable()->after('test_template_id');
        });
        
        echo "<span style='color:green'>Success! Column added.</span><br>";
    } else {
        echo "<span style='color:blue'>Info: Column 'student_test_template_id' already exists.</span><br>";
    }
    
    echo "<br>Checking 'student_test_templates' table...<br>";
    if (Schema::hasTable('student_test_templates')) {
        echo "<span style='color:green'>Found 'student_test_templates' table.</span><br>";
    } else {
        echo "<span style='color:red'>Error: 'student_test_templates' table NOT found. Please run migrations.</span><br>";
    }

} catch (\Exception $e) {
    echo "<br><span style='color:red'><b>Error:</b> " . $e->getMessage() . "</span><br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><hr>You can delete this file after the fix is verified.";
