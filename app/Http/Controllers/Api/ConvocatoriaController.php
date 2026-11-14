<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Convocatoria;

class ConvocatoriaController extends Controller
{
    public function index()
    {
        // Mostrar solo convocatorias publicadas
        $convocatorias = Convocatoria::where('Estado', 'Publicada')
            ->orderBy('Fecha_Fin', 'asc')
            ->get();

        return response()->json(['data' => $convocatorias], 200);
    }
}