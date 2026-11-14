<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evento;

class EventoController extends Controller
{
    public function index()
    {
        // Solo mostrar eventos activos ordenados por fecha
        $eventos = Evento::where('Estado', 'Activo')
            ->orderBy('Fecha_Inicio', 'asc')
            ->get();

        return response()->json(['data' => $eventos], 200);
    }
}