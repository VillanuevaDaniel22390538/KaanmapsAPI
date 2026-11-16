<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListCategoriasRequest;
use App\Http\Resources\CategoriaResource;
use App\Models\CategoriaProducto;
use App\Support\CategoriaTree;

class CategoriaController extends Controller
{
    public function index(ListCategoriasRequest $request)
    {
        $query = CategoriaProducto::query()
            ->activas()
            ->orderBy('orden')
            ->orderBy('Nombre_Categoria');

        if ($request->filled('padre_id')) {
            $query->where('ID_Categoria_Padre', (int) $request->input('padre_id'));
        }

        if ($request->filled('nivel')) {
            $ids = CategoriaTree::current()->idsDeNivel((int) $request->input('nivel'));
            $query->whereIn('ID_Categoria', $ids);
        }

        return CategoriaResource::collection($query->get());
    }
}
