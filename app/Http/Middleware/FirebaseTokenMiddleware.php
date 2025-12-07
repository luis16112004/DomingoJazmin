<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\FirebaseService;

class FirebaseTokenMiddleware
{
    protected $firebaseService;

    // Inyectamos nuestro servicio automáticamente
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Obtener el token del encabezado "Authorization: Bearer <token>"
        $authHeader = $request->header('Authorization');

        if (!$authHeader) {
            return response()->json(['message' => 'Token no proporcionado'], 401);
        }

        // Limpiamos la palabra "Bearer " para dejar solo el código
        $token = str_replace('Bearer ', '', $authHeader);

        // 2. Verificar el token con Firebase
        $verifiedToken = $this->firebaseService->verifyToken($token);

        if (!$verifiedToken) {
            return response()->json(['message' => 'Token inválido o expirado'], 401);
        }

        // (Opcional) Puedes guardar el UID de firebase en la petición para usarlo luego
        $uid = $verifiedToken->claims()->get('sub');
        $request->merge(['firebase_uid' => $uid]);

        return $next($request);
    }
}