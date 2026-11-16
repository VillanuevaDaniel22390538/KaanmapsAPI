<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductorRequest;
use App\Http\Requests\UpdateProductorRequest;
use App\Http\Resources\ProductorResource;
use App\Services\ProductorService;
use Illuminate\Http\Request;

class ProductorController extends Controller
{
    protected $service;

    public function __construct(ProductorService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $limitPerPage = $request->input('per_Page', 100);
        $filters = $request->only([
            'search', 'tipo_actividad', 'estado_verificacion', 'municipio', 'categoria_id',
        ]);

        $productores = $this->service->listarProductores($filters, $limitPerPage);

        return ProductorResource::collection($productores);
    }

    public function show($id)
    {
        $productor = $this->service->obtenerProductor($id);

        return new ProductorResource($productor);
    }

    public function store(StoreProductorRequest $request)
    {
        // Los datos ya están validados por StoreProductorRequest
        $productor = $this->service->crearProductor($request->validated());

        return (new ProductorResource($productor))
            ->additional([
                'success' => true,
                'message' => 'Productor creado exitosamente.',
            ])
            ->response()
            ->setStatusCode(201); // 201 Created
    }

    public function update(UpdateProductorRequest $request, $id)
    {
        $productor = $this->service->actualizarProductor($id, $request->validated());

        return (new ProductorResource($productor))
            ->additional([
                'success' => true,
                'message' => 'Productor actualizado correctamente.',
            ]);
    }

    public function destroy($id)
    {
        $this->service->eliminarProductor($id);

        return response()->json([
            'success' => true,
            'message' => 'Productor eliminado correctamente.',
        ], 200);
    }
}
