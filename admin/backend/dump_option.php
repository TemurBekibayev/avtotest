<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$option = App\Models\TestOption::first();
file_put_contents('option_out.json', $option ? $option->toJson(JSON_PRETTY_PRINT) : 'null');

echo "Done.\n";
