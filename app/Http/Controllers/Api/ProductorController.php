<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListProductoresRequest;
use App\Http\Requests\ShowProductorRequest;
use App\Http\Requests\StoreProductorRequest;
use App\Http\Requests\UpdateProductorRequest;
use App\Http\Resources\ProductorResource;
use App\Models\Productor;
use App\Services\ProductorService;
use Illuminate\Support\Facades\Gate;

class ProductorController extends Controller
{
    public function __construct(protected ProductorService $service)
    {
    }

    public function index(ListProductoresRequest $request)
    {
        return ProductorResource::collection(
            $this->service->listarProductores($request->filtros(), $request->perPage())
        );
    }

    public function show(ShowProductorRequest $request, int $id)
    {
        return new ProductorResource(
            $this->service->obtenerProductor($id, $request->incluirProductos())
        );
    }

    public function store(StoreProductorRequest $request)
    {
        $productor = $this->service->crearProductor($request->validated());

        return (new ProductorResource($productor))
            ->additional(['success' => true, 'message' => 'Productor creado exitosamente.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductorRequest $request, int $id)
    {
        $productor = $this->service->actualizarProductor($id, $request->validated());

        return (new ProductorResource($productor))
            ->additional(['success' => true, 'message' => 'Productor actualizado correctamente.']);
    }

    public function destroy(int $id)
    {
        Gate::authorize('delete', Productor::findOrFail($id));

        $this->service->eliminarProductor($id);

        return response()->json([
            'success' => true,
            'message' => 'Productor eliminado correctamente.',
        ], 200);
    }
}
