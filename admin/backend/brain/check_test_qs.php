<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$questions = \Illuminate\Support\Facades\DB::table('test_questions')->limit(5)->get();
foreach ($questions as $q) {
    echo "ID: {$q->id}, new_question_id: {$q->new_question_id}, file: {$q->question_file}\n";
}
