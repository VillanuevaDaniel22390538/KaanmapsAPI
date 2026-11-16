<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductorController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\UbicacionController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\EventoController;
use App\Http\Controllers\Api\ConvocatoriaController;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {

    // ==========================================
    // Rutas Públicas
    // ==========================================
    Route::middleware(['throttle:60,1'])->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        
        Route::get('/eventos', [EventoController::class, 'index']);
        Route::get('/convocatorias', [ConvocatoriaController::class, 'index']);
        
        Route::get('/categorias', [CategoriaController::class, 'index'])
            ->middleware('cache.headers:public;max_age=3600;etag');
        
        Route::get('/productores', [ProductorController::class, 'index']);
        Route::get('/productores/{id}', [ProductorController::class, 'show']);
        
        Route::get('/productos', [ProductoController::class, 'index']);
        Route::get('/productos/{id}', [ProductoController::class, 'show']);
    });

    // ==========================================
    // Rutas Protegidas (Requieren Token)
    // ==========================================
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        
        // Productores
        Route::post('/productores', [ProductorController::class, 'store']);
        Route::put('/productores/{id}', [ProductorController::class, 'update']);
        Route::delete('/productores/{id}', [ProductorController::class, 'destroy']);

        // Productos
        Route::post('/productos', [ProductoController::class, 'store']);
        Route::put('/productos/{id}', [ProductoController::class, 'update']);
        Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);

        // Ubicaciones
        Route::get('/ubicaciones', [UbicacionController::class, 'index']);
        Route::post('/ubicaciones', [UbicacionController::class, 'store']);
    });

});