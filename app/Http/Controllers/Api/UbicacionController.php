<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UbicacionProductor;
use Illuminate\Http\Request;

class UbicacionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['productor_id' => 'required|exists:productores,ID_Productor']);
        $ubicaciones = UbicacionProductor::where('ID_Productor', $request->productor_id)->get();
        return response()->json(['data' => $ubicaciones], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ID_Productor' => 'required|exists:productores,ID_Productor',
            'Latitud' => 'required|numeric',
            'Longitud' => 'required|numeric',
            'Direccion' => 'nullable|string',
            'Localidad' => 'nullable|string',
            'Municipio' => 'nullable|string',
        ]);

        $ubicacion = UbicacionProductor::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Ubicación registrada correctamente.',
            'data' => $ubicacion
        ], 201);
    }
}