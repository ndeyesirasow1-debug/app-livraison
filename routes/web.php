<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'API de livraison active',
        'version' => '1.0.0',
    ]);
});
