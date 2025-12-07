<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseService;

class ProviderController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    // Registrar una venta
    public function registrarVenta(Request $request)
    {
        $validatedData = $request->validate([
            'productos' => 'required|array',
            'productos.*.nombre' => 'required|string',
            'productos.*.cantidad' => 'required|numeric|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'metodo_pago' => 'required|string',
        ]);

        // Agregar el UID del usuario de Firebase
        $validatedData['usuario_id'] = $request->input('firebase_uid');

        $resultado = $this->firebaseService->guardarVenta($validatedData);

        if ($resultado['success']) {
            return response()->json([
                'message' => 'Venta registrada exitosamente',
                'venta_id' => $resultado['key'],
                'data' => $resultado['data']
            ], 201);
        }

        return response()->json([
            'message' => 'Error al registrar la venta',
            'error' => $resultado['error']
        ], 500);
    }

    // Obtener todas las ventas
    public function obtenerVentas()
    {
        $ventas = $this->firebaseService->obtenerVentas();
        
        return response()->json([
            'message' => 'Ventas obtenidas exitosamente',
            'ventas' => $ventas
        ], 200);
    }

    // Registrar un producto
    public function registrarProducto(Request $request)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'categoria' => 'nullable|string',
            'codigo_barras' => 'nullable|string',
        ]);

        $resultado = $this->firebaseService->guardarProducto($validatedData);

        if ($resultado['success']) {
            return response()->json([
                'message' => 'Producto registrado exitosamente',
                'producto_id' => $resultado['key'],
                'data' => $resultado['data']
            ], 201);
        }

        return response()->json([
            'message' => 'Error al registrar el producto',
            'error' => $resultado['error']
        ], 500);
    }

    // Obtener todos los productos
    public function obtenerProductos()
    {
        $productos = $this->firebaseService->obtenerProductos();
        
        return response()->json([
            'message' => 'Productos obtenidos exitosamente',
            'productos' => $productos
        ], 200);
    }
}