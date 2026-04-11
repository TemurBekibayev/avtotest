<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/setup-cpanel', function () {
    try {
        $output = [];
        
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
            'message' => $e->getMessage()
        ], 500);
    }
});
