<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/setup-cpanel', function () {
    try {
        $output = [];
        
        // Check for required JSON files before running
        $baseDir = resource_path('tests/savollar');
        $files = ['all_questions_uz.json', 'all_questions_ru.json', 'all_questions_kiril.json'];
        foreach ($files as $file) {
            if (!file_exists("$baseDir/$file")) {
                throw new \Exception("Missing required data file: resources/tests/savollar/$file");
            }
        }

        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output['migrate'] = \Illuminate\Support\Facades\Artisan::output();
        
        \Illuminate\Support\Facades\Artisan::call('tests:sync-shablons');
        $output['sync'] = \Illuminate\Support\Facades\Artisan::output();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Migration and Sync completed successfully!',
            'details' => $output
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString())
        ], 500);
    }
});

Route::get('/revert-cpanel', function () {
    try {
        $output = [];
        \Illuminate\Support\Facades\Artisan::call('migrate:rollback', ['--force' => true, '--step' => 1]);
        $output['rollback'] = \Illuminate\Support\Facades\Artisan::output();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Jarayon ortga qaytarildi (Rollback muvaffaqiyatli yakunlandi)',
            'details' => $output
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/system-logs', function () {
    $logFile = storage_path('logs/laravel.log');
    if (!file_exists($logFile)) {
        return "Log file not found.";
    }
    
    $lines = file($logFile);
    $lastLines = array_slice($lines, -500);
    
    return response("<pre>" . htmlspecialchars(implode("", $lastLines)) . "</pre>")
        ->header('Content-Type', 'text/html');
});
