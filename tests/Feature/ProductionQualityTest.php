<?php

namespace Tests\Feature;

use App\Models\ProductorImagen;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsCatalog;
use Tests\Concerns\UsesCategorias;
use Tests\TestCase;

class ProductionQualityTest extends TestCase
{
    use BuildsCatalog, UsesCategorias;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login');
    }

    public function test_rate_limiting_en_login(): void
    {
        config(['catalog.rate_limits.login' => 2]);
        $user = $this->crearUsuario(['Email' => 'loginlimit@example.com']);

        $payload = [
            'email' => $user->Email,
            'password' => 'incorrecta',
            'device_name' => 'flutter-test',
        ];

        $this->postJson('/v1/login', $payload)->assertStatus(422);
        $this->postJson('/v1/login', $payload)->assertStatus(422);

        $blocked = $this->postJson('/v1/login', $payload)->assertStatus(429);
        $this->assertNotEmpty($blocked->headers->get('Retry-After'));
    }

    public function test_rate_limiting_en_rutas_publicas(): void
    {
        config(['catalog.rate_limits.public' => 3]);
        $ip = '10.20.30.40';

        for ($i = 0; $i < 3; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->getJson('/v1/productos')
                ->assertOk()
                ->assertHeader('X-RateLimit-Limit', '3');
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->getJson('/v1/productos')
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_rate_limiting_en_rutas_de_escritura(): void
    {
        $this->sembrarCategorias();
        config(['catalog.rate_limits.writes' => 2]);

        $user = $this->crearUsuario();
        $productor = $this->crearProductor(['ID_Usuario' => $user->ID_Usuario]);
        Sanctum::actingAs($user);

        $body = [
            'ID_Productor' => $productor,
            'ID_Categoria' => $this->cat('Frutas'),
            'Nombre_Producto' => 'Mango Ataulfo',
        ];

        $this->postJson('/v1/productos', $body)->assertCreated();
        $this->postJson('/v1/productos', $body + ['Nombre_Producto' => 'Papaya'])->assertCreated();

        $this->postJson('/v1/productos', $body + ['Nombre_Producto' => 'Piña'])
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_headers_cors_en_preflight_y_peticiones_reales(): void
    {
        config(['cors.allowed_origins' => ['https://app.kaanmaps.gob.mx', 'https://admin.kaanmaps.gob.mx']]);

        $preflight = $this->options('/v1/productos', [], [
            'Origin' => 'https://app.kaanmaps.gob.mx',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'Authorization, Content-Type',
        ])->assertNoContent();

        $preflight->assertHeader('Access-Control-Allow-Origin', 'https://app.kaanmaps.gob.mx');
        $this->assertStringContainsString('GET', (string) $preflight->headers->get('Access-Control-Allow-Methods'));
        $this->assertSame('3600', $preflight->headers->get('Access-Control-Max-Age'));

        $actual = $this->getJson('/v1/productos', [
            'Origin' => 'https://app.kaanmaps.gob.mx',
        ])->assertOk();

        $actual->assertHeader('Access-Control-Allow-Origin', 'https://app.kaanmaps.gob.mx');
        $exposed = strtolower((string) $actual->headers->get('Access-Control-Expose-Headers'));
        $this->assertStringContainsString('etag', $exposed);
        $this->assertStringContainsString('x-ratelimit-limit', $exposed);

        // Origen no permitido no recibe el header Access-Control-Allow-Origin
        $disallowed = $this->getJson('/v1/productos', [
            'Origin' => 'https://malicioso.example.com',
        ])->assertOk();
        $this->assertNull($disallowed->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_cache_control_y_etag_en_productos_y_productores(): void
    {
        $this->sembrarCategorias();
        $productor = $this->crearProductor();
        $this->crearProducto($productor, $this->cat('Frutas'));

        foreach (['/v1/productos', '/v1/productores'] as $endpoint) {
            $res = $this->getJson($endpoint)->assertOk();
            $cc = (string) $res->headers->get('Cache-Control');
            $this->assertStringContainsString('no-cache', $cc);
            $etag = $res->headers->get('ETag');
            $this->assertNotEmpty($etag);

            $this->getJson($endpoint, ['If-None-Match' => $etag])->assertStatus(304);
        }
    }

    public function test_sin_hosts_ni_puertos_fijos_al_cambiar_app_url_y_disco(): void
    {
        $this->sembrarCategorias();

        config([
            'app.url' => 'https://api.kaanmaps.gob.mx',
            'filesystems.disks.public.url' => 'https://api.kaanmaps.gob.mx/storage',
        ]);
        URL::forceRootUrl('https://api.kaanmaps.gob.mx');
        Storage::forgetDisk('public');

        $user = $this->crearUsuario(['Avatar_URL' => 'avatars/productor.png']);
        $productor = $this->crearProductor([
            'ID_Usuario' => $user->ID_Usuario,
            'Logo_URL' => 'logos/logo.jpg',
        ]);
        ProductorImagen::create([
            'productor_id' => $productor,
            'ruta' => 'instalaciones/taller.jpg',
            'orden' => 1,
        ]);
        $this->crearProducto($productor, $this->cat('Frutas'), [
            'URLs_Imagenes' => json_encode(['productos/mango.jpg']),
        ]);

        try {
            $prodResp = $this->getJson('https://api.kaanmaps.gob.mx/v1/productos')->assertOk();
            $this->assertSame(['https://api.kaanmaps.gob.mx/storage/productos/mango.jpg'], $prodResp->json('data.0.imagenes'));
            $this->assertStringNotContainsString('localhost', $prodResp->getContent());
            $this->assertStringNotContainsString(':800', $prodResp->getContent());

            $productorResp = $this->getJson("https://api.kaanmaps.gob.mx/v1/productores/{$productor}")->assertOk();
            $this->assertSame('https://api.kaanmaps.gob.mx/storage/logos/logo.jpg', $productorResp->json('data.logo'));
            $this->assertSame('https://api.kaanmaps.gob.mx/storage/avatars/productor.png', $productorResp->json('data.usuario.avatar'));
            $this->assertSame(['https://api.kaanmaps.gob.mx/storage/instalaciones/taller.jpg'], $productorResp->json('data.imagenes_instalaciones'));
            $this->assertStringNotContainsString('localhost', $productorResp->getContent());
            $this->assertStringNotContainsString(':800', $productorResp->getContent());

            $docs = $this->get('https://api.kaanmaps.gob.mx/')->assertOk();
            $docs->assertSee('https://api.kaanmaps.gob.mx/v1');
            $docs->assertDontSee('localhost:8003');
        } finally {
            URL::forceRootUrl(null);
        }
    }
}
