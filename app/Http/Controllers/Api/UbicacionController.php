<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListUbicacionesRequest;
use App\Http\Requests\StoreUbicacionRequest;
use App\Http\Resources\UbicacionResource;
use App\Models\UbicacionProductor;

class UbicacionController extends Controller
{
    public function index(ListUbicacionesRequest $request)
    {
        $ubicaciones = UbicacionProductor::where('ID_Productor', $request->integer('productor_id'))
            ->orderByDesc('Es_Principal')
            ->orderBy('ID_Ubicacion')
            ->get();

        return UbicacionResource::collection($ubicaciones);
    }

    public function store(StoreUbicacionRequest $request)
    {
        $ubicacion = UbicacionProductor::create($request->validated());

        return (new UbicacionResource($ubicacion->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Ubicación registrada correctamente.',
            ])
            ->response()
            ->setStatusCode(201);
    }
}
