<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsCatalog;
use Tests\Concerns\UsesCategorias;
use Tests\TestCase;

class ProductosIndexTest extends TestCase
{
    use BuildsCatalog, UsesCategorias;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarCategorias();
    }

    public function test_estructura_y_formato_de_campos(): void
    {
        $productor = $this->crearProductor();
        $this->crearProducto($productor, $this->cat('Hamacas de Nylon'), [
            'Nombre_Producto' => 'Hamaca', 'Precio' => '3000.00', 'Moneda' => 'mxn',
            'URLs_Imagenes' => json_encode(['productos/h.jpg']), 'Etiquetas' => json_encode(['artesanal']),
            'Fecha_Registro' => '2026-10-10', 'Disponible' => 1,
        ]);

        $json = $this->getJson('/v1/productos')->assertOk()
            ->assertJsonStructure(['data' => [[
                'id', 'productor_id', 'categoria_id', 'categoria_raiz_id', 'subcategoria_id', 'tipo_id',
                'nombre', 'descripcion', 'precio', 'moneda', 'tipo_unidad', 'inventario', 'disponible',
                'imagenes', 'etiquetas', 'fecha_registro',
            ]], 'links', 'meta']);

        $p = $json->json('data.0');
        $this->assertSame('3000.00', $p['precio']);
        $this->assertIsString($p['precio']);
        $this->assertSame('MXN', $p['moneda']);
        $this->assertTrue($p['disponible']);
        $this->assertSame([rtrim(config('app.url'), '/').'/storage/productos/h.jpg'], $p['imagenes']);
        $this->assertSame(['artesanal'], $p['etiquetas']);
        $this->assertSame('2026-10-10', $p['fecha_registro']);
    }

    public function test_calcula_raiz_subcategoria_y_tipo_por_ancestros(): void
    {
        $productor = $this->crearProductor();
        $this->crearProducto($productor, $this->cat('Guayaberas'));          // nivel 3
        $this->crearProducto($productor, $this->cat('Hamaquería'));          // nivel 2
        $this->crearProducto($productor, $this->cat('Apicultura'));          // nivel 1 (dato viejo)

        $data = collect($this->getJson('/v1/productos?sort=precio_asc')->json('data'))->keyBy('categoria_id');

        $tipo = $data[$this->cat('Guayaberas')];
        $this->assertSame($this->cat('Artesanías'), $tipo['categoria_raiz_id']);
        $this->assertSame($this->cat('Textiles y Bordados'), $tipo['subcategoria_id']);
        $this->assertSame($this->cat('Guayaberas'), $tipo['tipo_id']);

        $sub = $data[$this->cat('Hamaquería')];
        $this->assertSame($this->cat('Artesanías'), $sub['categoria_raiz_id']);
        $this->assertSame($this->cat('Hamaquería'), $sub['subcategoria_id']);
        $this->assertNull($sub['tipo_id']);

        $raiz = $data[$this->cat('Apicultura')];
        $this->assertSame($this->cat('Apicultura'), $raiz['categoria_raiz_id']);
        $this->assertNull($raiz['subcategoria_id']);
        $this->assertNull($raiz['tipo_id']);
    }

    public function test_filtro_categoria_incluye_descendientes(): void
    {
        $productor = $this->crearProductor();
        $guay = $this->crearProducto($productor, $this->cat('Guayaberas'));
        $huip = $this->crearProducto($productor, $this->cat('Huipiles y Ternos'));
        $hama = $this->crearProducto($productor, $this->cat('Hamacas de Nylon'));
        $pollo = $this->crearProducto($productor, $this->cat('Pollos y Gallinas'));

        $ids = fn (string $q) => collect($this->getJson("/v1/productos?{$q}")->assertOk()->json('data'))->pluck('id')->sort()->values()->all();

        // raíz → todo su subárbol
        $this->assertSame([$guay, $huip, $hama], $ids('categoria_id='.$this->cat('Artesanías')));
        // subcategoría → ella y sus tipos
        $this->assertSame([$guay, $huip], $ids('categoria_id='.$this->cat('Textiles y Bordados')));
        // tipo → solo ese
        $this->assertSame([$guay], $ids('categoria_id='.$this->cat('Guayaberas')));
        // inexistente → vacío
        $this->assertSame([], $ids('categoria_id=99999'));
        $this->assertNotContains($pollo, $ids('categoria_id='.$this->cat('Artesanías')));
    }

    public function test_filtros_productor_disponible_etiqueta_y_busqueda(): void
    {
        $a = $this->crearProductor();
        $b = $this->crearProductor();
        $cat = $this->cat('Frutas');
        $p1 = $this->crearProducto($a, $cat, ['Nombre_Producto' => 'Papaya maradol', 'Descripcion' => 'Dulce', 'Disponible' => 1, 'Etiquetas' => json_encode(['fresco', 'demo'])]);
        $p2 = $this->crearProducto($a, $cat, ['Nombre_Producto' => 'Mango', 'Descripcion' => 'Con sabor a papaya', 'Disponible' => 0, 'Etiquetas' => json_encode(['demo'])]);
        $p3 = $this->crearProducto($b, $cat, ['Nombre_Producto' => 'Piña 100%', 'Descripcion' => 'x', 'Disponible' => 1, 'Etiquetas' => json_encode([])]);

        $ids = fn (string $q) => collect($this->getJson("/v1/productos?{$q}")->assertOk()->json('data'))->pluck('id')->sort()->values()->all();

        $this->assertSame([$p3], $ids("productor_id={$b}"));
        $this->assertSame([$p1, $p3], $ids('disponible=true'));
        $this->assertSame([$p1, $p3], $ids('disponible=1'));
        $this->assertSame([$p2], $ids('disponible=false'));
        $this->assertSame([$p1, $p2], $ids('etiqueta=demo'));
        $this->assertSame([$p1], $ids('etiqueta=fresco'));
        $this->assertSame([$p1, $p2], $ids('q=papaya'));         // nombre y descripción
        $this->assertSame([$p3], $ids('q='.urlencode('100%')));   // % no es comodín
        $this->assertSame([$p3], $ids('q='.urlencode('%')));      // "%" literal solo aparece en p3
        $this->assertSame([$p2], $ids("productor_id={$a}&disponible=false&q=mango"));
    }

    public function test_orden(): void
    {
        $prod = $this->crearProductor();
        $cat = $this->cat('Frutas');
        $viejo = $this->crearProducto($prod, $cat, ['Precio' => '50.00', 'Fecha_Registro' => '2026-01-01']);
        $medio = $this->crearProducto($prod, $cat, ['Precio' => '10.00', 'Fecha_Registro' => '2026-03-01']);
        $nuevo = $this->crearProducto($prod, $cat, ['Precio' => '90.00', 'Fecha_Registro' => '2026-06-01']);

        $ids = fn (string $q) => collect($this->getJson("/v1/productos?{$q}")->json('data'))->pluck('id')->all();

        $this->assertSame([$nuevo, $medio, $viejo], $ids(''));                    // default: recientes
        $this->assertSame([$nuevo, $medio, $viejo], $ids('sort=recientes'));
        $this->assertSame([$medio, $viejo, $nuevo], $ids('sort=precio_asc'));
        $this->assertSame([$nuevo, $viejo, $medio], $ids('sort=precio_desc'));
        $this->getJson('/v1/productos?sort=raro')->assertStatus(422);
    }

    public function test_paginacion_per_page_por_defecto_15_y_maximo_100(): void
    {
        $prod = $this->crearProductor();
        $cat = $this->cat('Frutas');
        for ($i = 0; $i < 120; $i++) {
            $this->crearProducto($prod, $cat);
        }

        $this->getJson('/v1/productos')->assertOk()
            ->assertJsonPath('meta.per_page', 15)->assertJsonPath('meta.total', 120)
            ->assertJsonCount(15, 'data');

        $this->getJson('/v1/productos?per_page=500')->assertOk()
            ->assertJsonPath('meta.per_page', 100)->assertJsonCount(100, 'data');

        $this->getJson('/v1/productos?per_page=0')->assertStatus(422);
        $this->getJson('/v1/productos?per_page=abc')->assertStatus(422);

        // los filtros viajan en los links de paginación
        $links = $this->getJson('/v1/productos?per_page=10&sort=precio_asc')->json('links.next');
        $this->assertStringContainsString('sort=precio_asc', $links);
        $this->assertStringContainsString('per_page=10', $links);
    }

    public function test_no_hay_n_mas_1(): void
    {
        $prod = $this->crearProductor();
        $cat = $this->cat('Guayaberas');
        for ($i = 0; $i < 30; $i++) {
            $this->crearProducto($prod, $cat);
        }

        $this->getJson('/v1/productos'); // calienta cache del árbol
        DB::enableQueryLog();
        $this->getJson('/v1/productos?per_page=30')->assertOk()->assertJsonCount(30, 'data');
        $consultas = count(DB::getQueryLog());

        $this->assertLessThanOrEqual(3, $consultas, "Demasiadas consultas: {$consultas}");
    }

    public function test_solo_productos_de_productores_activos_y_flag_de_verificados(): void
    {
        $activo = $this->crearProductor(['Estado' => 'Activo', 'Estado_Verificacion' => 'Pendiente']);
        $verificado = $this->crearProductor(['Estado' => 'Activo', 'Estado_Verificacion' => 'Verificado']);
        $inactivo = $this->crearProductor(['Estado' => 'Inactivo', 'Estado_Verificacion' => 'Verificado']);
        $cat = $this->cat('Frutas');
        $pa = $this->crearProducto($activo, $cat);
        $pv = $this->crearProducto($verificado, $cat);
        $pi = $this->crearProducto($inactivo, $cat);

        $ids = fn () => collect($this->getJson('/v1/productos')->json('data'))->pluck('id')->sort()->values()->all();

        $this->assertSame([$pa, $pv], $ids());

        config(['catalog.only_verified' => true]);
        $this->assertSame([$pv], $ids());
        $this->assertNotContains($pi, $ids());
    }
}
