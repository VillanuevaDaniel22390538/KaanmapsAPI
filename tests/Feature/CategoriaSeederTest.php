<?php

namespace Tests\Feature;

use App\Models\CategoriaProducto;
use App\Support\CategoriaTree;
use Database\Seeders\CategoriaSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoriaSeederTest extends TestCase
{
    /** Simula el estado real de kaanmapsdb antes de sembrar. */
    private function sembrarLegado(): void
    {
        $filas = [
            [1, 'Apicultura', null, '#FFC107'],
            [2, 'Agricultura', null, '#4CAF50'],
            [3, 'Ganadería', null, '#8D6E63'],
            [4, 'Pesca', null, '#03A9F4'],
            [5, 'Artesanías', null, '#E91E63'],
            [12, 'Textiles Tradicionales', 5, '#BA68C8'],
            [23, 'Acuacultura', 4, '#00BCD4'],
            [25, 'Joyería Artesanal', 5, '#9C27B0'],
            [28, 'Otro', null, null],
        ];
        foreach ($filas as [$id, $nombre, $padre, $color]) {
            DB::table('categorias_productos')->insert([
                'ID_Categoria' => $id, 'Nombre_Categoria' => $nombre, 'slug' => \Illuminate\Support\Str::slug($nombre),
                'ID_Categoria_Padre' => $padre, 'Color_Hex' => $color, 'Descripcion' => "desc {$nombre}",
            ]);
        }
    }

    public function test_es_idempotente(): void
    {
        $this->sembrarLegado();

        $this->seed(CategoriaSeeder::class);
        $total = CategoriaProducto::count();
        $snapshot = CategoriaProducto::orderBy('ID_Categoria')->get()->toArray();

        $this->seed(CategoriaSeeder::class);
        $this->seed(CategoriaSeeder::class);

        $this->assertSame($total, CategoriaProducto::count());
        $this->assertSame($snapshot, CategoriaProducto::orderBy('ID_Categoria')->get()->toArray());
    }

    public function test_reutiliza_filas_existentes_por_nombre_y_conserva_slug_y_datos(): void
    {
        $this->sembrarLegado();
        $this->seed(CategoriaSeeder::class);

        foreach ([1 => 'Apicultura', 2 => 'Agricultura', 3 => 'Ganadería', 5 => 'Artesanías', 25 => 'Joyería Artesanal'] as $id => $nombre) {
            $fila = CategoriaProducto::find($id);
            $this->assertSame($nombre, $fila->Nombre_Categoria);
            $this->assertTrue($fila->activo, "{$nombre} debe quedar activa");
        }

        $this->assertSame('#FFC107', CategoriaProducto::find(1)->Color_Hex);
        $this->assertSame('desc Apicultura', CategoriaProducto::find(1)->Descripcion);
        $this->assertSame('apicultura', CategoriaProducto::find(1)->slug);
        $this->assertSame(5, CategoriaProducto::find(25)->ID_Categoria_Padre);
    }

    public function test_desactiva_sin_borrar_lo_que_no_aplica(): void
    {
        $this->sembrarLegado();
        $this->seed(CategoriaSeeder::class);

        foreach ([4 => 'Pesca', 12 => 'Textiles Tradicionales', 23 => 'Acuacultura', 28 => 'Otro'] as $id => $nombre) {
            $fila = CategoriaProducto::find($id);
            $this->assertNotNull($fila, "{$nombre} no debe borrarse");
            $this->assertFalse($fila->activo, "{$nombre} debe quedar inactiva");
        }
    }

    public function test_acuicolas_cuelga_de_ganaderia_y_el_arbol_tiene_maximo_tres_niveles(): void
    {
        $this->sembrarLegado();
        $this->seed(CategoriaSeeder::class);

        $acuicolas = CategoriaProducto::where('Nombre_Categoria', 'Acuicolas')->first();
        $this->assertSame(3, $acuicolas->ID_Categoria_Padre);
        $this->assertTrue($acuicolas->activo);

        $tree = CategoriaTree::current();
        $this->assertSame([], $tree->idsDeNivel(4));

        $camaron = CategoriaProducto::where('Nombre_Categoria', 'Camarón y Mariscos')->first();
        $this->assertSame([3, $acuicolas->ID_Categoria, $camaron->ID_Categoria], $tree->ruta($camaron->ID_Categoria));
    }

    public function test_slugs_unicos_y_estables(): void
    {
        $this->seed(CategoriaSeeder::class);
        $slugs = CategoriaProducto::pluck('slug');
        $this->assertSame($slugs->count(), $slugs->unique()->count());

        $antes = CategoriaProducto::pluck('slug', 'ID_Categoria')->all();
        $this->seed(CategoriaSeeder::class);
        $this->assertSame($antes, CategoriaProducto::pluck('slug', 'ID_Categoria')->all());
    }

    public function test_el_orden_de_las_subcategorias_sigue_el_arbol_de_la_app(): void
    {
        $this->seed(CategoriaSeeder::class);
        $tree = CategoriaTree::current();

        $subs = collect($tree->idsDeNivel(2))
            ->map(fn ($id) => CategoriaProducto::find($id))
            ->sortBy(fn ($c) => [CategoriaProducto::find($c->ID_Categoria_Padre)->orden, $c->orden])
            ->values();

        $this->assertSame('Aves de Corral', $subs[0]->Nombre_Categoria);
        $this->assertSame('Joyería Artesanal', $subs[9]->Nombre_Categoria);
        $this->assertSame('Miel de Abeja', $subs[10]->Nombre_Categoria);
        $this->assertSame('Semillas e Injertos', $subs[17]->Nombre_Categoria);
    }
}
