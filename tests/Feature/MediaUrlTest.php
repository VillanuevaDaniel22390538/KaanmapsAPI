<?php

namespace Tests\Feature;

use App\Support\MediaUrl;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsCatalog;
use Tests\TestCase;

class MediaUrlTest extends TestCase
{
    use BuildsCatalog;

    private function base(): string
    {
        return rtrim(config('app.url'), '/');
    }

    public function test_resuelve_rutas_relativas_con_app_url_y_el_disco_configurado(): void
    {
        $this->assertSame($this->base().'/storage/avatars/a.png', MediaUrl::resolve('avatars/a.png'));
        $this->assertSame($this->base().'/storage/avatars/a.png', MediaUrl::resolve('/storage/avatars/a.png'));
        $this->assertNull(MediaUrl::resolve(null));
        $this->assertNull(MediaUrl::resolve(''));
    }

    public function test_corrige_en_lectura_urls_locales_antiguas_sin_hosts_fijos(): void
    {
        $this->assertSame($this->base().'/storage/avatars/a.png', MediaUrl::resolve('http://localhost:8001/storage/avatars/a.png'));
        $this->assertSame($this->base().'/storage/avatars/a.png', MediaUrl::resolve('http://192.168.1.50:8000/storage/avatars/a.png'));
    }

    public function test_respeta_urls_externas_y_no_publica_assets_empaquetados_de_la_app(): void
    {
        $google = 'https://lh3.googleusercontent.com/a/ACg8oc=s96-c';
        $this->assertSame($google, MediaUrl::resolve($google));

        $this->assertNull(MediaUrl::resolve('assets/images/app/Business/GenericImages/B_ganaderia.png'));
    }

    public function test_el_disco_sale_de_la_configuracion(): void
    {
        config(['filesystems.media_disk' => 's3']);
        $this->assertSame('s3', MediaUrl::disk());
    }

    public function test_los_endpoints_devuelven_imagenes_absolutas(): void
    {
        $usuario = $this->crearUsuario(['Avatar_URL' => 'avatars/u.png']);
        $productor = $this->crearProductor([
            'ID_Usuario' => $usuario->ID_Usuario,
            'Logo_URL' => '/storage/logos/l.jpg',
        ]);
        $cat = DB::table('categorias_productos')->insertGetId(['Nombre_Categoria' => 'X', 'slug' => 'x'], 'ID_Categoria');
        $this->crearProducto($productor, $cat, [
            'URLs_Imagenes' => json_encode(['productos/a.jpg', 'http://localhost:8001/storage/productos/b.jpg', 'assets/images/app/Products/GenericImages/P.png']),
        ]);

        $p = $this->getJson("/v1/productores/{$productor}")->assertOk()->json('data');
        $this->assertSame($this->base().'/storage/logos/l.jpg', $p['logo']);
        $this->assertSame($this->base().'/storage/avatars/u.png', $p['usuario']['avatar']);

        $prod = $this->getJson('/v1/productos')->assertOk()->json('data.0');
        $this->assertSame([
            $this->base().'/storage/productos/a.jpg',
            $this->base().'/storage/productos/b.jpg',
        ], $prod['imagenes']);
    }

    public function test_la_migracion_de_datos_normaliza_y_es_reversible_y_repetible(): void
    {
        $migracion = require base_path('database/migrations/2026_11_16_000000_normalize_media_urls.php');
        $migracion->down(); // estado previo: sin tabla de respaldo

        $u1 = $this->crearUsuario(['Avatar_URL' => 'http://localhost:8001/storage/avatars/a.png']);
        $u2 = $this->crearUsuario(['Avatar_URL' => '/storage/avatars/b.png']);
        $u3 = $this->crearUsuario(['Avatar_URL' => 'https://lh3.googleusercontent.com/a/x=s96-c']);
        $u4 = $this->crearUsuario(['Avatar_URL' => 'avatars/ya-relativa.png']);
        $assets = 'assets/images/app/Business/GenericImages/B_x.png';
        $prod = $this->crearProductor(['Logo_URL' => $assets]);
        $cat = DB::table('categorias_productos')->insertGetId(['Nombre_Categoria' => 'Y', 'slug' => 'y'], 'ID_Categoria');
        $pid = $this->crearProducto($prod, $cat, [
            'URLs_Imagenes' => json_encode(['http://localhost:8001/storage/productos/p.jpg', 'productos/q.jpg']),
        ]);

        $migracion->up();

        $avatar = fn ($u) => DB::table('usuarios')->where('ID_Usuario', $u->ID_Usuario)->value('Avatar_URL');
        $this->assertSame('avatars/a.png', $avatar($u1));
        $this->assertSame('avatars/b.png', $avatar($u2));
        $this->assertSame('https://lh3.googleusercontent.com/a/x=s96-c', $avatar($u3));
        $this->assertSame('avatars/ya-relativa.png', $avatar($u4));
        $this->assertSame($assets, DB::table('productores')->where('ID_Productor', $prod)->value('Logo_URL'));
        $this->assertSame(
            ['productos/p.jpg', 'productos/q.jpg'],
            json_decode(DB::table('productos')->where('ID_Producto', $pid)->value('URLs_Imagenes'), true)
        );
        $this->assertSame(3, DB::table('media_url_backups')->count()); // u1, u2, producto

        // Repetir up sobre datos ya normalizados no genera respaldos nuevos
        $migracion->down();   // restaura originales
        $migracion->up();     // vuelve a normalizar
        $this->assertSame(3, DB::table('media_url_backups')->count());

        // Reversible: down restaura los valores exactos
        $migracion->down();
        $this->assertSame('http://localhost:8001/storage/avatars/a.png', $avatar($u1));
        $this->assertSame('/storage/avatars/b.png', $avatar($u2));
        $this->assertSame(
            ['http://localhost:8001/storage/productos/p.jpg', 'productos/q.jpg'],
            json_decode(DB::table('productos')->where('ID_Producto', $pid)->value('URLs_Imagenes'), true)
        );

        $migracion->up(); // deja el estado coherente para el rollback de RefreshDatabase
    }
}
