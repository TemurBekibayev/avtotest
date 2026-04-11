<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ShablonQuestion;

$q = ShablonQuestion::with(['translations', 'options.translations'])->first();

echo "JSON ID: " . $q->json_id . "\n";
echo "Image Path: " . $q->image_path . "\n";
echo "Translations:\n";
foreach ($q->translations as $t) {
    echo "- [{$t->language}] text: " . substr($t->question_text, 0, 50) . "...\n";
}
echo "Options:\n";
foreach ($q->options as $o) {
    echo "- ID: {$o->id}, is_correct: {$o->is_correct}\n";
    foreach ($o->translations as $ot) {
        echo "  - [{$ot->language}] text: '{$ot->option_text}'\n";
    }
}
