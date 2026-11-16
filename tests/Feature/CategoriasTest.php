<?php

namespace Tests\Feature;

use Database\Seeders\CategoriaSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoriasTest extends TestCase
{
    public function test_devuelve_lista_plana_con_campos_en_snake_case_y_niveles(): void
    {
        $this->seed(CategoriaSeeder::class);

        $response = $this->getJson('/v1/categorias')->assertOk();

        $response->assertJsonStructure(['data' => [[
            'id', 'nombre', 'slug', 'padre_id', 'nivel', 'descripcion', 'color_hex', 'icono_url', 'orden',
        ]]]);

        $data = collect($response->json('data'));

        // 4 raíces + 18 subcategorías + tipos sembrados (3+3+3+2 ganadería, 4+2+3+3+3 artesanías)
        $this->assertSame(4, $data->where('nivel', 1)->count());
        $this->assertSame(18, $data->where('nivel', 2)->count());
        $this->assertSame(11 + 15, $data->where('nivel', 3)->count());
        $this->assertSame(0, $data->where('nivel', 4)->count());

        $raiz = $data->firstWhere('slug', 'ganaderia');
        $this->assertNull($raiz['padre_id']);
        $this->assertSame(1, $raiz['nivel']);

        $tipo = $data->firstWhere('slug', 'pollos-y-gallinas');
        $this->assertSame(3, $tipo['nivel']);
        $this->assertSame($data->firstWhere('slug', 'aves-de-corral')['id'], $tipo['padre_id']);

        // Ningún nombre de campo en Pascal_Snake
        foreach (array_keys($data->first()) as $key) {
            $this->assertSame(strtolower($key), $key);
        }
    }

    public function test_solo_devuelve_activas_y_no_expone_las_desactivadas(): void
    {
        $this->seed(CategoriaSeeder::class);
        DB::table('categorias_productos')->insert(['Nombre_Categoria' => 'Fuera', 'slug' => 'fuera', 'activo' => 0]);

        $slugs = collect($this->getJson('/v1/categorias')->json('data'))->pluck('slug');

        $this->assertFalse($slugs->contains('fuera'));
    }

    public function test_filtra_por_nivel_y_por_padre(): void
    {
        $this->seed(CategoriaSeeder::class);

        $nivel2 = $this->getJson('/v1/categorias?nivel=2')->assertOk()->json('data');
        $this->assertCount(18, $nivel2);
        $this->assertSame([2], collect($nivel2)->pluck('nivel')->unique()->values()->all());

        $aves = collect($nivel2)->firstWhere('slug', 'aves-de-corral');
        $tipos = $this->getJson('/v1/categorias?padre_id='.$aves['id'])->assertOk()->json('data');
        $this->assertSame(
            ['Pollos y Gallinas', 'Patos', 'Pavos'],
            collect($tipos)->pluck('nombre')->all(),   // respeta "orden"
        );
    }

    public function test_valida_los_filtros(): void
    {
        $this->getJson('/v1/categorias?nivel=9')->assertStatus(422);
        $this->getJson('/v1/categorias?padre_id=abc')->assertStatus(422);
    }

    public function test_envia_cache_control_y_etag_y_responde_304(): void
    {
        $this->seed(CategoriaSeeder::class);

        $first = $this->getJson('/v1/categorias')->assertOk();
        $cc = $first->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cc);
        $this->assertStringContainsString('max-age=3600', $cc);

        $etag = $first->headers->get('ETag');
        $this->assertNotEmpty($etag);

        $this->getJson('/v1/categorias', ['If-None-Match' => $etag])->assertStatus(304);
    }
}
