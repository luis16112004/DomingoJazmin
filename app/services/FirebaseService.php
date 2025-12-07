<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class FirebaseService
{
    protected $auth;
    protected $database;

    public function __construct()
    {
        // Buscar primero en la raíz del proyecto
        $credentialsPath = base_path('firebase_credentials.json');
        
        // Si no existe, buscar en storage/app
        if (!file_exists($credentialsPath)) {
            $credentialsPath = storage_path('app/firebase_credentials.json');
        }

        // Si no existe ninguno, usar variable de entorno (para Railway)
        if (!file_exists($credentialsPath) && env('FIREBASE_CREDENTIALS_JSON')) {
            $credentialsPath = sys_get_temp_dir() . '/firebase_credentials.json';
            file_put_contents($credentialsPath, env('FIREBASE_CREDENTIALS_JSON'));
        }

        // Verificar que existe el archivo
        if (!file_exists($credentialsPath)) {
            throw new \Exception('No se encontró el archivo de credenciales de Firebase');
        }

        $factory = (new Factory)
            ->withServiceAccount($credentialsPath)
            ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));

        $this->auth = $factory->createAuth();
        $this->database = $factory->createDatabase();
    }

    public function verifyToken(string $token)
    {
        try {
            return $this->auth->verifyIdToken($token);
        } catch (FailedToVerifyToken $e) {
            \Log::error('Token verification failed: ' . $e->getMessage());
            return null;
        } catch (\Throwable $e) {
            \Log::error('Firebase error: ' . $e->getMessage());
            return null;
        }
    }

    public function guardarVenta(array $datosVenta)
    {
        try {
            // Agregar timestamp
            $datosVenta['fecha'] = date('Y-m-d H:i:s');
            
            $nuevaVenta = $this->database->getReference('ventas')->push($datosVenta);
            return [
                'success' => true,
                'key' => $nuevaVenta->getKey(),
                'data' => $datosVenta
            ];
        } catch (\Throwable $e) {
            \Log::error('Error guardando venta: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function obtenerVentas()
    {
        try {
            $ventas = $this->database->getReference('ventas')->getValue();
            return $ventas ?? [];
        } catch (\Throwable $e) {
            \Log::error('Error obteniendo ventas: ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerProductos()
    {
        try {
            $productos = $this->database->getReference('productos')->getValue();
            return $productos ?? [];
        } catch (\Throwable $e) {
            \Log::error('Error obteniendo productos: ' . $e->getMessage());
            return [];
        }
    }

    public function guardarProducto(array $datosProducto)
    {
        try {
            $nuevoProducto = $this->database->getReference('productos')->push($datosProducto);
            return [
                'success' => true,
                'key' => $nuevoProducto->getKey(),
                'data' => $datosProducto
            ];
        } catch (\Throwable $e) {
            \Log::error('Error guardando producto: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ========== MÉTODOS DE USUARIOS ==========

    public function crearUsuario(string $email, string $password, array $datosAdicionales = [])
    {
        try {
            // Crear usuario en Firebase Authentication
            $userProperties = [
                'email' => $email,
                'password' => $password,
                'emailVerified' => false,
            ];

            if (isset($datosAdicionales['nombre'])) {
                $userProperties['displayName'] = $datosAdicionales['nombre'];
            }

            $createdUser = $this->auth->createUser($userProperties);
            $uid = $createdUser->uid;

            // Guardar datos adicionales en Realtime Database
            $userData = [
                'uid' => $uid,
                'email' => $email,
                'nombre' => $datosAdicionales['nombre'] ?? '',
                'telefono' => $datosAdicionales['telefono'] ?? '',
                'rol' => $datosAdicionales['rol'] ?? 'cajero',
                'fecha_registro' => date('Y-m-d H:i:s'),
                'activo' => true
            ];

            $this->database->getReference('usuarios/' . $uid)->set($userData);

            return [
                'success' => true,
                'user' => $userData
            ];

        } catch (\Kreait\Firebase\Exception\Auth\EmailExists $e) {
            return [
                'success' => false,
                'error' => 'El correo electrónico ya está registrado'
            ];
        } catch (\Throwable $e) {
            \Log::error('Error creando usuario: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function obtenerUsuario(string $uid)
    {
        try {
            $usuario = $this->database->getReference('usuarios/' . $uid)->getValue();
            return $usuario;
        } catch (\Throwable $e) {
            \Log::error('Error obteniendo usuario: ' . $e->getMessage());
            return null;
        }
    }

    public function listarUsuarios()
    {
        try {
            $usuarios = $this->database->getReference('usuarios')->getValue();
            return $usuarios ?? [];
        } catch (\Throwable $e) {
            \Log::error('Error listando usuarios: ' . $e->getMessage());
            return [];
        }
    }

    public function actualizarUsuario(string $uid, array $datos)
    {
        try {
            // Actualizar en Authentication si hay cambios de email o displayName
            $authUpdates = [];
            if (isset($datos['nombre'])) {
                $authUpdates['displayName'] = $datos['nombre'];
            }

            if (!empty($authUpdates)) {
                $this->auth->updateUser($uid, $authUpdates);
            }

            // Actualizar en Realtime Database
            $this->database->getReference('usuarios/' . $uid)->update($datos);

            $usuarioActualizado = $this->obtenerUsuario($uid);

            return [
                'success' => true,
                'user' => $usuarioActualizado
            ];

        } catch (\Throwable $e) {
            \Log::error('Error actualizando usuario: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function eliminarUsuario(string $uid)
    {
        try {
            // Eliminar de Authentication
            $this->auth->deleteUser($uid);

            // Eliminar de Realtime Database
            $this->database->getReference('usuarios/' . $uid)->remove();

            return [
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ];

        } catch (\Throwable $e) {
            \Log::error('Error eliminando usuario: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}