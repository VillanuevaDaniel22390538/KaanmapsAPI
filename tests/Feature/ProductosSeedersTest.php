<?php

namespace Tests\Feature;

use App\Models\Producto;
use Database\Seeders\ProductosDemoSeeder;
use Database\Seeders\ReasignarCategoriasProductosSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsCatalog;
use Tests\Concerns\UsesCategorias;
use Tests\TestCase;

class ProductosSeedersTest extends TestCase
{
    use BuildsCatalog, UsesCategorias;

    public function test_reasigna_productos_de_la_raiz_a_su_subcategoria_y_es_idempotente(): void
    {
        $this->sembrarCategorias();
        $prod = $this->crearProductor();

        // Estado real: ID_Categoria raíz + id local de la app (1..18) en subcategoria_id
        $pollo = $this->crearProducto($prod, $this->cat('Ganadería'), ['subcategoria_id' => 1]);    // Aves de Corral
        $acui = $this->crearProducto($prod, $this->cat('Ganadería'), ['subcategoria_id' => 4]);     // Acuicolas
        $joya = $this->crearProducto($prod, $this->cat('Artesanías'), ['subcategoria_id' => 10]);   // Joyería Artesanal
        $miel = $this->crearProducto($prod, $this->cat('Apicultura'), ['subcategoria_id' => 11]);   // Miel de Abeja
        $semi = $this->crearProducto($prod, $this->cat('Agricultura'), ['subcategoria_id' => 18]);  // Semillas e Injertos
        $sinSub = $this->crearProducto($prod, $this->cat('Agricultura'));                           // sin dato: no se toca
        $incoherente = $this->crearProducto($prod, $this->cat('Apicultura'), ['subcategoria_id' => 1]); // raíz ≠ raíz de la sub

        $this->seed(ReasignarCategoriasProductosSeeder::class);

        $cat = fn (int $id) => DB::table('productos')->where('ID_Producto', $id)->value('ID_Categoria');
        $this->assertSame($this->cat('Aves de Corral'), $cat($pollo));
        $this->assertSame($this->cat('Acuicolas'), $cat($acui));
        $this->assertSame($this->cat('Joyería Artesanal'), $cat($joya));
        $this->assertSame($this->cat('Miel de Abeja'), $cat($miel));
        $this->assertSame($this->cat('Semillas e Injertos'), $cat($semi));
        $this->assertSame($this->cat('Agricultura'), $cat($sinSub));
        $this->assertSame($this->cat('Apicultura'), $cat($incoherente));

        $antes = DB::table('productos')->orderBy('ID_Producto')->get()->toArray();
        $this->seed(ReasignarCategoriasProductosSeeder::class);
        $this->assertEquals($antes, DB::table('productos')->orderBy('ID_Producto')->get()->toArray());
    }

    public function test_los_productos_reasignados_aparecen_en_los_filtros(): void
    {
        $this->sembrarCategorias();
        $prod = $this->crearProductor();
        $id = $this->crearProducto($prod, $this->cat('Ganadería'), ['subcategoria_id' => 3]); // Bovinos
        $this->seed(ReasignarCategoriasProductosSeeder::class);

        $res = $this->getJson('/v1/productos?categoria_id='.$this->cat('Bovinos'))->assertOk();
        $this->assertSame([$id], collect($res->json('data'))->pluck('id')->all());
        $this->assertSame($this->cat('Bovinos'), $res->json('data.0.subcategoria_id'));
    }

    public function test_pide_correr_categorias_primero(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->seed(ReasignarCategoriasProductosSeeder::class);
    }

    public function test_demo_es_idempotente_y_cubre_varias_subcategorias(): void
    {
        $this->sembrarCategorias();
        $prod = $this->crearProductor(['categoria_id' => $this->cat('Artesanías')]);

        $this->seed(ProductosDemoSeeder::class);
        $total = Producto::count();
        $this->assertGreaterThanOrEqual(20, $total);

        $this->seed(ProductosDemoSeeder::class);
        $this->seed(ProductosDemoSeeder::class);
        $this->assertSame($total, Producto::count());

        // todos con etiqueta demo, en categorías específicas (nivel >= 2)
        $this->assertSame($total, Producto::whereJsonContains('Etiquetas', 'demo')->count());
        $subs = Producto::query()->get()
            ->map(fn ($p) => \App\Support\CategoriaTree::current()->enNivel($p->ID_Categoria, 2))->unique();
        $this->assertGreaterThanOrEqual(10, $subs->count());

        // Hay disponibles y no disponibles para probar el filtro
        $this->assertGreaterThan(0, Producto::where('Disponible', 1)->count());
        $this->assertGreaterThan(0, Producto::where('Disponible', 0)->count());

        // Artesanías usa el productor de esa categoría
        $hamaca = Producto::where('Nombre_Producto', 'Hamaca de algodón matrimonial')->first();
        $this->assertSame($prod, $hamaca->ID_Productor);

        // y se pueden filtrar por categoría
        $this->getJson('/v1/productos?categoria_id='.$this->cat('Hamaquería').'&per_page=100')
            ->assertOk()->assertJsonCount(3, 'data');
    }
}
