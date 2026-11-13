<?php

use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [App\Http\Controllers\AuthController::class, 'register']);
Route::post('/auth/login', [App\Http\Controllers\AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/profile', [App\Http\Controllers\AuthController::class, 'profile']);
    Route::post('/auth/logout', [App\Http\Controllers\AuthController::class, 'logout']);

    Route::get('/produits', [App\Http\Controllers\ProduitController::class, 'index']);
    Route::get('/produits/{id}', [App\Http\Controllers\ProduitController::class, 'show']);
    Route::post('/produits', [App\Http\Controllers\ProduitController::class, 'store']);

    Route::get('/commandes', [App\Http\Controllers\CommandeController::class, 'index']);
    Route::post('/commandes', [App\Http\Controllers\CommandeController::class, 'store']);

    Route::get('/livraisons', [App\Http\Controllers\LivraisonController::class, 'index']);
    Route::put('/livraisons/{id}/assign', [App\Http\Controllers\LivraisonController::class, 'assign']);
    Route::put('/livraisons/{id}/statut', [App\Http\Controllers\LivraisonController::class, 'updateStatus']);
});
