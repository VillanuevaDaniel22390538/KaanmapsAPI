<?php

namespace Tests\Concerns;

use App\Models\CategoriaProducto;
use Database\Seeders\CategoriaSeeder;

trait UsesCategorias
{
    protected function sembrarCategorias(): void
    {
        $this->seed(CategoriaSeeder::class);
    }

    /** Id de una categoría del árbol sembrado por su nombre. */
    protected function cat(string $nombre): int
    {
        return (int) CategoriaProducto::where('Nombre_Categoria', $nombre)->value('ID_Categoria');
    }
}
