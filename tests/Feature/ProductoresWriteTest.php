<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsCatalog;
use Tests\Concerns\UsesCategorias;
use Tests\TestCase;

class ProductoresWriteTest extends TestCase
{
    use BuildsCatalog, UsesCategorias;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarCategorias();
        Storage::fake('public', ['url' => rtrim(config('app.url'), '/').'/storage']);
    }

    public function test_requieren_token(): void
    {
        $this->postJson('/v1/productores', [])->assertUnauthorized();
        $this->putJson('/v1/productores/1', [])->assertUnauthorized();
        $this->deleteJson('/v1/productores/1')->assertUnauthorized();
    }

    public function test_el_usuario_crea_su_negocio_con_logo_y_datos_nuevos(): void
    {
        $user = $this->crearUsuario();
        Sanctum::actingAs($user);

        $res = $this->post('/v1/productores', [
            'ID_Usuario' => $user->ID_Usuario,
            'Nombre_Negocio' => 'Mi Negocio',
            'Tipo_Actividad' => 'Apicultura',
            'categoria_id' => $this->cat('Miel de Abeja'),
            'email_contacto' => 'a@b.mx',
            'telefono_contacto' => '9981234567',
            'horario' => 'Lun-Vie 9:00-18:00',
            'Logo_URL' => UploadedFile::fake()->image('logo.png', 1200, 900),
            'imagenes_instalaciones' => [UploadedFile::fake()->image('i1.jpg'), UploadedFile::fake()->image('i2.jpg')],
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre_negocio', 'Mi Negocio')
            ->assertJsonPath('data.categoria_principal_id', $this->cat('Miel de Abeja'))
            ->assertJsonPath('data.email', 'a@b.mx')
            ->assertJsonPath('data.telefono_contacto', '9981234567')
            ->assertJsonPath('data.horario', 'Lun-Vie 9:00-18:00');

        $base = rtrim(config('app.url'), '/').'/storage/';
        $this->assertStringStartsWith($base.'logos/', $res->json('data.logo'));
        $this->assertCount(2, $res->json('data.imagenes_instalaciones'));

        // En BD: solo rutas relativas
        $fila = DB::table('productores')->where('ID_Productor', $res->json('data.id'))->first();
        $this->assertStringStartsWith('logos/', $fila->Logo_URL);
        Storage::disk('public')->assertExists($fila->Logo_URL);
        $this->assertSame(2, DB::table('productor_imagenes')->where('productor_id', $fila->ID_Productor)->count());
        $this->assertSame('Pendiente', $fila->Estado_Verificacion);
    }

    public function test_tipo_actividad_es_opcional_si_viene_la_categoria_principal_y_acepta_alias(): void
    {
        $user = $this->crearUsuario();
        Sanctum::actingAs($user);

        $this->postJson('/v1/productores', [
            'usuario_id' => $user->ID_Usuario,
            'nombre_negocio' => 'Con alias',
            'categoria_principal_id' => $this->cat('Artesanías'),
            'email' => 'x@y.mx',
        ])->assertCreated()
            ->assertJsonPath('data.nombre_negocio', 'Con alias')
            ->assertJsonPath('data.tipo_actividad', 'Artesanías') // derivado
            ->assertJsonPath('data.email', 'x@y.mx');
    }

    public function test_validaciones(): void
    {
        $user = $this->crearUsuario();
        Sanctum::actingAs($user);

        // sin tipo ni categoría
        $this->postJson('/v1/productores', ['ID_Usuario' => $user->ID_Usuario, 'Nombre_Negocio' => 'X'])
            ->assertStatus(422)->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['Tipo_Actividad', 'categoria_id']);

        $this->postJson('/v1/productores', [
            'ID_Usuario' => $user->ID_Usuario, 'Nombre_Negocio' => 'X', 'Tipo_Actividad' => 'Minería',
        ])->assertStatus(422)->assertJsonValidationErrors('Tipo_Actividad');

        // categoría inactiva
        DB::table('categorias_productos')->insert(['ID_Categoria' => 600, 'Nombre_Categoria' => 'Muerta', 'slug' => 'muerta', 'activo' => 0]);
        $this->postJson('/v1/productores', [
            'ID_Usuario' => $user->ID_Usuario, 'Nombre_Negocio' => 'X', 'categoria_id' => 600,
        ])->assertStatus(422)->assertJsonValidationErrors('categoria_id');

        $this->postJson('/v1/productores', [
            'ID_Usuario' => $user->ID_Usuario, 'Nombre_Negocio' => 'X', 'Tipo_Actividad' => 'Agricultura', 'email_contacto' => 'no-es-email',
        ])->assertStatus(422)->assertJsonValidationErrors('email_contacto');
    }

    public function test_no_puede_crear_un_negocio_a_nombre_de_otro_usuario(): void
    {
        Sanctum::actingAs($this->crearUsuario());
        $otro = $this->crearUsuario();

        $this->postJson('/v1/productores', [
            'ID_Usuario' => $otro->ID_Usuario, 'Nombre_Negocio' => 'X', 'Tipo_Actividad' => 'Agricultura',
        ])->assertForbidden();

        Sanctum::actingAs($this->crearUsuario(['Tipo_Usuario' => 'Capturista_Gobierno']));
        $this->postJson('/v1/productores', [
            'ID_Usuario' => $otro->ID_Usuario, 'Nombre_Negocio' => 'X', 'Tipo_Actividad' => 'Agricultura',
        ])->assertCreated();
    }

    public function test_el_dueno_actualiza_pero_no_puede_auto_verificarse(): void
    {
        $user = $this->crearUsuario();
        $id = $this->crearProductor(['ID_Usuario' => $user->ID_Usuario, 'Estado_Verificacion' => 'Pendiente']);
        Sanctum::actingAs($user);

        $this->putJson("/v1/productores/{$id}", [
            'Nombre_Negocio' => 'Renombrado', 'Slogan' => 'Nuevo', 'horario' => 'Todos los días',
            'Estado_Verificacion' => 'Verificado', 'Estado' => 'Activo',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre_negocio', 'Renombrado')
            ->assertJsonPath('data.horario', 'Todos los días')
            ->assertJsonPath('data.estado_verificacion', 'Pendiente'); // ignorado

        $this->assertSame('Pendiente', DB::table('productores')->where('ID_Productor', $id)->value('Estado_Verificacion'));
    }

    public function test_el_personal_si_puede_verificar_y_el_extra_reemplaza_imagenes(): void
    {
        $id = $this->crearProductor();
        DB::table('productor_imagenes')->insert(['productor_id' => $id, 'ruta' => 'instalaciones/vieja.jpg', 'orden' => 0]);
        Sanctum::actingAs($this->crearUsuario(['Tipo_Usuario' => 'Admin_Gobierno']));

        $res = $this->post("/v1/productores/{$id}", [
            '_method' => 'PUT',
            'Estado_Verificacion' => 'Verificado',
            'imagenes_instalaciones' => [UploadedFile::fake()->image('nueva.jpg')],
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('data.estado_verificacion', 'Verificado')
            ->assertJsonCount(1, 'data.imagenes_instalaciones');

        $this->assertStringContainsString('/instalaciones/', $res->json('data.imagenes_instalaciones.0'));
        $this->assertStringNotContainsString('vieja', $res->json('data.imagenes_instalaciones.0'));
    }

    public function test_otro_usuario_no_puede_editar_ni_borrar(): void
    {
        $dueno = $this->crearUsuario();
        $id = $this->crearProductor(['ID_Usuario' => $dueno->ID_Usuario]);
        Sanctum::actingAs($this->crearUsuario());

        $this->putJson("/v1/productores/{$id}", ['Nombre_Negocio' => 'Hack'])->assertForbidden();
        $this->deleteJson("/v1/productores/{$id}")->assertForbidden();
        $this->assertDatabaseHas('productores', ['ID_Productor' => $id, 'Nombre_Negocio' => DB::table('productores')->where('ID_Productor', $id)->value('Nombre_Negocio')]);
    }

    public function test_productores_sin_dueno_solo_los_gestiona_el_personal(): void
    {
        $id = $this->crearProductor(['ID_Usuario' => null]);

        Sanctum::actingAs($this->crearUsuario(['Tipo_Usuario' => 'Productor']));
        $this->putJson("/v1/productores/{$id}", ['Nombre_Negocio' => 'x'])->assertForbidden();

        Sanctum::actingAs($this->crearUsuario(['Tipo_Usuario' => 'Capturista_Gobierno']));
        $this->putJson("/v1/productores/{$id}", ['Nombre_Negocio' => 'Editado'])->assertOk();
        $this->deleteJson("/v1/productores/{$id}")->assertForbidden(); // capturista no borra

        Sanctum::actingAs($this->crearUsuario(['Tipo_Usuario' => 'SuperAdmin']));
        $this->deleteJson("/v1/productores/{$id}")->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('productores', ['ID_Productor' => $id]);
    }

    public function test_el_dueno_puede_borrar_su_negocio_y_404_si_no_existe(): void
    {
        $user = $this->crearUsuario();
        $id = $this->crearProductor(['ID_Usuario' => $user->ID_Usuario]);
        Sanctum::actingAs($user);

        $this->deleteJson("/v1/productores/{$id}")->assertOk();
        $this->deleteJson('/v1/productores/9999')->assertNotFound();
        $this->putJson('/v1/productores/9999', ['Nombre_Negocio' => 'x'])->assertNotFound();
    }
}
