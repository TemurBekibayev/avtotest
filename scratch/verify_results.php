<?php
require __DIR__ . '/../admin/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../admin/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\StudentTestTemplate;
use App\Models\TestResult;
use App\Models\User;

$user = User::whereHas('student')->first();
if (!$user) {
    echo "No student user found.\n";
    exit;
}

$studentId = $user->student->id;
echo "Testing for student ID: $studentId\n";

$templates = StudentTestTemplate::with(['latestResult' => function($query) use ($studentId) {
    $query->where('student_id', $studentId);
}])->get();

foreach ($templates as $tpl) {
    echo "Template: {$tpl->name} (ID: {$tpl->id})\n";
    if ($tpl->latestResult) {
        echo "  Latest Result: {$tpl->latestResult->score}% (ID: {$tpl->latestResult->id})\n";
    } else {
        echo "  No result found.\n";
    }
}
