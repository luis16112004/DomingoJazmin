<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\FirebaseTokenMiddleware;

// Rutas públicas (sin autenticación)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API funcionando correctamente',
        'timestamp' => now()
    ]);
});

// ========== RUTAS DE AUTENTICACIÓN (Públicas) ==========
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify', [AuthController::class, 'verifyToken']);
});

// Rutas protegidas (requieren autenticación de Firebase)
Route::middleware([FirebaseTokenMiddleware::class])->group(function () {
    
    // Información del usuario autenticado
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    // Gestión de usuarios (solo admin)
    Route::get('/auth/users', [AuthController::class, 'listarUsuarios']);
    Route::put('/auth/users/{uid}', [AuthController::class, 'actualizarUsuario']);
    
    // Rutas de ventas
    Route::post('/ventas', [ProviderController::class, 'registrarVenta']);
    Route::get('/ventas', [ProviderController::class, 'obtenerVentas']);
    
    // Rutas de productos
    Route::post('/productos', [ProviderController::class, 'registrarProducto']);
    Route::get('/productos', [ProviderController::class, 'obtenerProductos']);
});

// ⭐ Rutas para DESARROLLO (sin autenticación)
// IMPORTANTE: Eliminar estas rutas en producción
Route::prefix('dev')->group(function () {
    // GET - Consultar
    Route::get('/productos', [ProviderController::class, 'obtenerProductos']);
    Route::get('/ventas', [ProviderController::class, 'obtenerVentas']);
    
    // POST - Crear
    Route::post('/productos', [ProviderController::class, 'registrarProducto']);
    Route::post('/ventas', [ProviderController::class, 'registrarVenta']);
    
    // Usuarios (desarrollo)
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/users', [AuthController::class, 'listarUsuarios']);
});