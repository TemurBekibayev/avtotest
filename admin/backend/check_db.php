<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\TestQuestion;
use Illuminate\Support\Facades\DB;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$questions = TestQuestion::select('id', 'new_question_id', 'question_file')->limit(10)->get();

foreach ($questions as $q) {
    echo "ID: {$q->id}, New ID: {$q->new_question_id}, File: {$q->question_file}\n";
}
