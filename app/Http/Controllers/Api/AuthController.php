<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseService;

class AuthController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Registrar nuevo usuario
     * POST /api/auth/register
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string',
            'rol' => 'nullable|string|in:admin,cajero,vendedor',
        ]);

        try {
            $resultado = $this->firebaseService->crearUsuario(
                $validatedData['email'],
                $validatedData['password'],
                [
                    'nombre' => $validatedData['nombre'],
                    'telefono' => $validatedData['telefono'] ?? null,
                    'rol' => $validatedData['rol'] ?? 'cajero',
                ]
            );

            if ($resultado['success']) {
                return response()->json([
                    'message' => 'Usuario registrado exitosamente',
                    'user' => $resultado['user']
                ], 201);
            }

            return response()->json([
                'message' => 'Error al registrar usuario',
                'error' => $resultado['error']
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar token de usuario
     * POST /api/auth/verify
     */
    public function verifyToken(Request $request)
    {
        $validatedData = $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $verifiedToken = $this->firebaseService->verifyToken($validatedData['token']);

            if (!$verifiedToken) {
                return response()->json([
                    'message' => 'Token inválido o expirado'
                ], 401);
            }

            $uid = $verifiedToken->claims()->get('sub');
            $email = $verifiedToken->claims()->get('email');

            // Obtener datos adicionales del usuario desde Realtime Database
            $userData = $this->firebaseService->obtenerUsuario($uid);

            return response()->json([
                'message' => 'Token válido',
                'user' => [
                    'uid' => $uid,
                    'email' => $email,
                    'nombre' => $userData['nombre'] ?? null,
                    'rol' => $userData['rol'] ?? 'cajero',
                    'telefono' => $userData['telefono'] ?? null,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al verificar token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información del usuario autenticado
     * GET /api/auth/me
     */
    public function me(Request $request)
    {
        $uid = $request->input('firebase_uid');

        if (!$uid) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        try {
            $userData = $this->firebaseService->obtenerUsuario($uid);

            if (!$userData) {
                return response()->json([
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            return response()->json([
                'message' => 'Usuario obtenido exitosamente',
                'user' => $userData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todos los usuarios (solo admin)
     * GET /api/auth/users
     */
    public function listarUsuarios()
    {
        try {
            $usuarios = $this->firebaseService->listarUsuarios();

            return response()->json([
                'message' => 'Usuarios obtenidos exitosamente',
                'users' => $usuarios
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar usuario
     * PUT /api/auth/users/{uid}
     */
    public function actualizarUsuario(Request $request, $uid)
    {
        $validatedData = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'telefono' => 'nullable|string',
            'rol' => 'nullable|string|in:admin,cajero,vendedor',
        ]);

        try {
            $resultado = $this->firebaseService->actualizarUsuario($uid, $validatedData);

            if ($resultado['success']) {
                return response()->json([
                    'message' => 'Usuario actualizado exitosamente',
                    'user' => $resultado['user']
                ], 200);
            }

            return response()->json([
                'message' => 'Error al actualizar usuario',
                'error' => $resultado['error']
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}