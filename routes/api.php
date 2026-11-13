<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'API de livraison active',
        'version' => '1.0.0',
    ]);
});

Route::post('/auth/register', [App\Http\Controllers\AuthController::class, 'register']);
Route::post('/auth/login', [App\Http\Controllers\AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/profile', [App\Http\Controllers\AuthController::class, 'profile']);
    Route::post('/auth/logout', [App\Http\Controllers\AuthController::class, 'logout']);

    Route::get('/produits', [App\Http\Controllers\ProduitController::class, 'index']);
    Route::get('/produits/{id}', [App\Http\Controllers\ProduitController::class, 'show']);
    Route::post('/produits', [App\Http\Controllers\ProduitController::class, 'store'])->middleware('role:admin,gestionnaire');

    Route::get('/categories', [App\Http\Controllers\CategorieController::class, 'index']);
    Route::get('/categories/{id}', [App\Http\Controllers\CategorieController::class, 'show']);
    Route::post('/categories', [App\Http\Controllers\CategorieController::class, 'store'])->middleware('role:admin,gestionnaire');

    Route::get('/commandes', [App\Http\Controllers\CommandeController::class, 'index']);
    Route::post('/commandes', [App\Http\Controllers\CommandeController::class, 'store']);

    Route::get('/livraisons', [App\Http\Controllers\LivraisonController::class, 'index']);
    Route::put('/livraisons/{id}/assign', [App\Http\Controllers\LivraisonController::class, 'assign'])->middleware('role:admin,gestionnaire');
    Route::put('/livraisons/{id}/statut', [App\Http\Controllers\LivraisonController::class, 'updateStatus']);

    Route::get('/paiements', [App\Http\Controllers\PaiementController::class, 'index']);
    Route::post('/paiements', [App\Http\Controllers\PaiementController::class, 'store']);

    Route::get('/clients/me', [App\Http\Controllers\ClientController::class, 'me']);
    Route::post('/clients/profile', [App\Http\Controllers\ClientController::class, 'storeProfile']);

    Route::get('/livreurs', [App\Http\Controllers\LivreurController::class, 'index']);
    Route::get('/livreurs/me', [App\Http\Controllers\LivreurController::class, 'me']);
    Route::post('/livreurs/profile', [App\Http\Controllers\LivreurController::class, 'storeProfile']);
    Route::put('/livreurs/{id}/disponibilite', [App\Http\Controllers\LivreurController::class, 'setDisponibilite']);
    Route::post('/livreurs/position', [App\Http\Controllers\LivreurController::class, 'updatePosition']);

    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
});
