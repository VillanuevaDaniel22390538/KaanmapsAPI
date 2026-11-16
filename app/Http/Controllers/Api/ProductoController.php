<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Http\Resources\ProductoResource;
use App\Services\ProductoService;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    protected $service;

    public function __construct(ProductoService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        // 1. Extraemos el límite
        $limitPerPage = $request->input('per_Page', 100);
        $filters = $request->only(['productor_id', 'disponible', 'categoria_id', 'subcategoria_id']);
        $productos = $this->service->listarProductos($filters, $limitPerPage);

        return ProductoResource::collection($productos);
    }

    public function show($id)
    {
        $producto = $this->service->obtenerProducto($id);

        return new ProductoResource($producto);
    }

    public function store(StoreProductoRequest $request)
    {
        $producto = $this->service->crearProducto($request->validated());

        return (new ProductoResource($producto))->additional([
            'success' => true,
            'message' => 'Producto creado exitosamente.',
        ])->response()->setStatusCode(201);
    }

    public function update(UpdateProductoRequest $request, $id)
    {
        $producto = $this->service->actualizarProducto($id, $request->validated());

        return (new ProductoResource($producto))->additional([
            'success' => true,
            'message' => 'Producto actualizado correctamente.',
        ]);
    }

    public function destroy($id)
    {
        $this->service->eliminarProducto($id);

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente.',
        ], 200);
    }
}
