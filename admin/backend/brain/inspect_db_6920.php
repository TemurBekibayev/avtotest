<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ShablonQuestion;

$q = ShablonQuestion::where('json_id', 6920)->with(['translations', 'options.translations'])->first();

if (!$q) {
    echo "Question 6920 NOT FOUND in DB!\n";
    exit;
}

echo "Question ID: {$q->id}, Json ID: {$q->json_id}\n";
echo "Image Path: {$q->image_path}\n";
echo "Translations:\n";
foreach ($q->translations as $t) {
    echo "  - [{$t->language}] text: '{$t->question_text}'\n";
}
echo "Options count: " . count($q->options) . "\n";
foreach ($q->options as $idx => $o) {
    echo "  - Option $idx [is_correct: {$o->is_correct}]:\n";
    foreach ($o->translations as $ot) {
        echo "    - [{$ot->language}] text: '{$ot->option_text}'\n";
    }
}
