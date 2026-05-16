<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'app' => 'Ashwani Shop API',
    'version' => '1.0',
    'docs' => '/api/v1',
]));
