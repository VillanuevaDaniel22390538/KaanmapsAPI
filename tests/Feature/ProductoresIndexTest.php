<?php

namespace Tests\Feature;

use App\Models\ProductorImagen;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsCatalog;
use Tests\Concerns\UsesCategorias;
use Tests\TestCase;

class ProductoresIndexTest extends TestCase
{
    use BuildsCatalog, UsesCategorias;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarCategorias();
    }

    private function ids(string $query = ''): array
    {
        return collect($this->getJson('/v1/productores?'.$query)->assertOk()->json('data'))->pluck('id')->sort()->values()->all();
    }

    public function test_estructura_completa_y_sin_datos_personales(): void
    {
        $usuario = $this->crearUsuario(['Nombre_Usuario' => 'secreto_user', 'Telefono' => '9990001111', 'Avatar_URL' => 'avatars/a.png']);
        $id = $this->crearProductor([
            'ID_Usuario' => $usuario->ID_Usuario,
            'Nombre_Negocio' => 'Miel Maya', 'Tipo_Actividad' => 'Apicultura', 'categoria_id' => $this->cat('Miel de Abeja'),
            'Logo_URL' => 'logos/l.jpg', 'Slogan' => 'Dulce', 'Estado_Verificacion' => 'Verificado',
            'telefono_contacto' => '9981112233', 'email_contacto' => 'hola@miel.mx', 'facebook' => 'fb.com/miel',
            'instagram' => '@miel', 'horario' => 'Lun-Vie 9:00-18:00', 'Fecha_Registro' => '2026-02-03',
        ]);
        ProductorImagen::create(['productor_id' => $id, 'ruta' => 'instalaciones/2.jpg', 'orden' => 2]);
        ProductorImagen::create(['productor_id' => $id, 'ruta' => 'instalaciones/1.jpg', 'orden' => 1]);
        $this->crearUbicacion($id);

        $res = $this->getJson('/v1/productores')->assertOk()
            ->assertJsonStructure(['data' => [[
                'id', 'nombre_negocio', 'descripcion', 'tipo_actividad', 'categoria_principal_id', 'slogan', 'logo',
                'estado_verificacion', 'telefono_contacto', 'email', 'facebook', 'instagram', 'horario',
                'imagenes_instalaciones', 'fecha_registro', 'usuario' => ['avatar'],
                'ubicaciones' => [['id', 'productor_id', 'latitud', 'longitud', 'direccion', 'localidad', 'municipio', 'estado', 'pais', 'precision_geolocalizacion', 'es_principal']],
            ]], 'links', 'meta']);

        $p = $res->json('data.0');
        $base = rtrim(config('app.url'), '/').'/storage/';
        $this->assertSame($base.'logos/l.jpg', $p['logo']);
        $this->assertSame([$base.'instalaciones/1.jpg', $base.'instalaciones/2.jpg'], $p['imagenes_instalaciones']);
        $this->assertSame($base.'avatars/a.png', $p['usuario']['avatar']);
        $this->assertSame($this->cat('Miel de Abeja'), $p['categoria_principal_id']);
        $this->assertSame('hola@miel.mx', $p['email']);
        $this->assertSame('Lun-Vie 9:00-18:00', $p['horario']);
        $this->assertSame('2026-02-03', $p['fecha_registro']);
        $this->assertIsFloat($p['ubicaciones'][0]['latitud']);

        // Datos personales de la cuenta NO se publican; tampoco `productos` en la lista
        $this->assertSame(['avatar'], array_keys($p['usuario']));
        $this->assertStringNotContainsString('secreto_user', $res->getContent());
        $this->assertStringNotContainsString('9990001111', $res->getContent());
        $this->assertArrayNotHasKey('productos', $p);
    }

    public function test_todas_las_claves_son_snake_case_minuscula(): void
    {
        $id = $this->crearProductor();
        $this->crearUbicacion($id);
        $json = $this->getJson('/v1/productores')->json('data.0');

        $revisar = function (array $arr) use (&$revisar) {
            foreach ($arr as $k => $v) {
                if (is_string($k)) {
                    $this->assertSame(strtolower($k), $k, "Clave no minúscula: {$k}");
                }
                if (is_array($v)) {
                    $revisar($v);
                }
            }
        };
        $revisar($json);
    }

    public function test_sin_usuario_ni_imagenes_no_falla(): void
    {
        $this->crearProductor(['ID_Usuario' => null, 'hora_apertura' => '09:00:00', 'hora_cierre' => '18:00:00']);

        $p = $this->getJson('/v1/productores')->assertOk()->json('data.0');
        $this->assertNull($p['usuario']['avatar']);
        $this->assertSame([], $p['imagenes_instalaciones']);
        $this->assertSame([], $p['ubicaciones']);
        $this->assertSame('09:00-18:00', $p['horario']); // derivado de columnas deprecadas
    }

    public function test_tipo_actividad_se_deriva_de_la_categoria_si_falta(): void
    {
        $this->crearProductor(['Tipo_Actividad' => null, 'categoria_id' => $this->cat('Hamaquería')]);

        $this->assertSame('Artesanías', $this->getJson('/v1/productores')->json('data.0.tipo_actividad'));
    }

    public function test_filtro_categoria_por_principal_o_por_productos_con_descendientes(): void
    {
        $porPrincipal = $this->crearProductor(['categoria_id' => $this->cat('Textiles y Bordados')]);
        $porProductos = $this->crearProductor(['categoria_id' => null]);
        $this->crearProducto($porProductos, $this->cat('Guayaberas'));
        $otro = $this->crearProductor(['categoria_id' => $this->cat('Apicultura')]);
        $this->crearProducto($otro, $this->cat('Miel de Abeja'));
        $sinNada = $this->crearProductor(['categoria_id' => null]);

        // raíz Artesanías: ambos (principal en el subárbol / productos en el subárbol)
        $this->assertSame([$porPrincipal, $porProductos], $this->ids('categoria_id='.$this->cat('Artesanías')));
        $this->assertSame([$porPrincipal, $porProductos], $this->ids('categoria_id='.$this->cat('Textiles y Bordados')));
        // tipo: solo quien tiene productos de ese tipo (la principal es la subcategoría, que no está DENTRO del tipo)
        $this->assertSame([$porProductos], $this->ids('categoria_id='.$this->cat('Guayaberas')));
        $this->assertSame([$otro], $this->ids('categoria_id='.$this->cat('Apicultura')));
        $this->assertSame([], $this->ids('categoria_id=99999'));
        $this->assertNotContains($sinNada, $this->ids('categoria_id='.$this->cat('Artesanías')));
    }

    public function test_filtros_q_search_tipo_actividad_municipio_y_verificacion(): void
    {
        $a = $this->crearProductor(['Nombre_Negocio' => 'Hamacas del Sur', 'Descripcion_Negocio' => 'Tejidas', 'Tipo_Actividad' => 'Artesanías', 'Estado_Verificacion' => 'Verificado']);
        $b = $this->crearProductor(['Nombre_Negocio' => 'Granja', 'Descripcion_Negocio' => 'Vende hamacas también', 'Tipo_Actividad' => 'Ganadería']);
        $this->crearUbicacion($a, ['Municipio' => 'Bacalar']);
        $this->crearUbicacion($b, ['Municipio' => 'Tulum']);

        $this->assertSame([$a, $b], $this->ids('q=hamacas'));
        $this->assertSame([$a, $b], $this->ids('search=hamacas'));            // alias antiguo
        $this->assertSame([$a], $this->ids('q=del+sur'));
        $this->assertSame([$b], $this->ids('tipo_actividad='.urlencode('Ganadería')));
        $this->assertSame([$b], $this->ids('municipio=tulum'));
        $this->assertSame([$a], $this->ids('estado_verificacion=Verificado'));
        $this->getJson('/v1/productores?estado_verificacion=Otro')->assertStatus(422);
    }

    public function test_per_page_por_defecto_15_y_maximo_100(): void
    {
        for ($i = 0; $i < 110; $i++) {
            $this->crearProductor();
        }

        $this->getJson('/v1/productores')->assertOk()->assertJsonPath('meta.per_page', 15)->assertJsonCount(15, 'data');
        $this->getJson('/v1/productores?per_page=999')->assertOk()->assertJsonPath('meta.per_page', 100)->assertJsonCount(100, 'data');
        $this->getJson('/v1/productores?per_page=0')->assertStatus(422);
        $this->assertStringContainsString('per_page=20', $this->getJson('/v1/productores?per_page=20')->json('links.next'));
    }

    public function test_visibilidad_activos_y_flag_solo_verificados(): void
    {
        $pendiente = $this->crearProductor(['Estado' => 'Activo', 'Estado_Verificacion' => 'Pendiente']);
        $verificado = $this->crearProductor(['Estado' => 'Activo', 'Estado_Verificacion' => 'Verificado']);
        $inactivo = $this->crearProductor(['Estado' => 'Inactivo', 'Estado_Verificacion' => 'Verificado']);

        $this->assertSame([$pendiente, $verificado], $this->ids());

        config(['catalog.only_verified' => true]);
        $this->assertSame([$verificado], $this->ids());
        $this->assertNotContains($inactivo, $this->ids());
    }

    public function test_no_hay_n_mas_1(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $usuario = $this->crearUsuario();
            $id = $this->crearProductor(['ID_Usuario' => $usuario->ID_Usuario]);
            $this->crearUbicacion($id);
            ProductorImagen::create(['productor_id' => $id, 'ruta' => 'instalaciones/x.jpg', 'orden' => 1]);
        }

        DB::enableQueryLog();
        $this->getJson('/v1/productores?per_page=25')->assertOk()->assertJsonCount(25, 'data');
        $consultas = count(DB::getQueryLog());

        // count + productores + usuarios + ubicaciones + imagenes
        $this->assertLessThanOrEqual(6, $consultas, "Demasiadas consultas: {$consultas}");
    }
}
