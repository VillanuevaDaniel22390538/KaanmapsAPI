<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListProductosRequest;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use App\Services\ProductoService;
use Illuminate\Support\Facades\Gate;

class ProductoController extends Controller
{
    public function __construct(protected ProductoService $service)
    {
    }

    public function index(ListProductosRequest $request)
    {
        return ProductoResource::collection(
            $this->service->listarProductos($request->filtros(), $request->perPage())
        );
    }

    public function show(int $id)
    {
        return new ProductoResource($this->service->obtenerProducto($id));
    }

    public function store(StoreProductoRequest $request)
    {
        $producto = $this->service->crearProducto($request->validated());

        return (new ProductoResource($producto))
            ->additional(['success' => true, 'message' => 'Producto creado exitosamente.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductoRequest $request, int $id)
    {
        $producto = $this->service->actualizarProducto($id, $request->validated());

        return (new ProductoResource($producto))
            ->additional(['success' => true, 'message' => 'Producto actualizado correctamente.']);
    }

    public function destroy(int $id)
    {
        Gate::authorize('delete', Producto::findOrFail($id));

        $this->service->eliminarProducto($id);

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente.',
        ], 200);
    }
}
