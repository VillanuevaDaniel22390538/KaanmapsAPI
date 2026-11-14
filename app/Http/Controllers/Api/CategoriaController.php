<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategoriaProducto;

class CategoriaController extends Controller
{
    public function index()
    {
        // Se consultan directamente porque es un catálogo simple de solo lectura
        $categorias = CategoriaProducto::all();
        return response()->json(['data' => $categorias], 200);
    }
}