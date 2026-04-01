<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting path migration...\n";

// 1. Update test_questions table
$affectedQuestions = DB::table('test_questions')
    ->where('question_file', 'like', '/storage/tests/images/%')
    ->get();

foreach ($affectedQuestions as $q) {
    if (str_contains($q->question_file, 'images changed')) continue;
    $newPath = str_replace('/storage/tests/images/', '/storage/tests/images changed/', $q->question_file);
    DB::table('test_questions')->where('id', $q->id)->update(['question_file' => $newPath]);
    echo "Updated Question ID {$q->id}: {$q->question_file} -> {$newPath}\n";
}

// 2. Update answers table
$affectedAnswers = DB::table('answers')
    ->where('answer_resource', 'like', '/storage/tests/videos/%')
    ->get();

foreach ($affectedAnswers as $a) {
    if (str_contains($a->answer_resource, 'videos changed')) continue;
    $newPath = str_replace('/storage/tests/videos/', '/storage/tests/videos changed/', $a->answer_resource);
    DB::table('answers')->where('id', $a->id)->update(['answer_resource' => $newPath]);
    echo "Updated Answer ID {$a->id}: {$a->answer_resource} -> {$newPath}\n";
}

echo "Migration complete.\n";
