<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsCatalog;
use Tests\Concerns\UsesCategorias;
use Tests\TestCase;

class ProductosWriteTest extends TestCase
{
    use BuildsCatalog, UsesCategorias;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarCategorias();
        Storage::fake('public', ['url' => rtrim(config('app.url'), '/').'/storage']);
    }

    private function dueno(): array
    {
        $user = $this->crearUsuario();
        $productor = $this->crearProductor(['ID_Usuario' => $user->ID_Usuario]);

        return [$user, $productor];
    }

    public function test_rutas_de_escritura_requieren_token(): void
    {
        $this->postJson('/v1/productos', [])->assertUnauthorized();
        $this->putJson('/v1/productos/1', [])->assertUnauthorized();
        $this->deleteJson('/v1/productos/1')->assertUnauthorized();
    }

    public function test_el_dueno_crea_un_producto_con_imagenes_y_se_guardan_rutas_relativas(): void
    {
        [$user, $productor] = $this->dueno();
        Sanctum::actingAs($user);

        $response = $this->post('/v1/productos', [
            'ID_Productor' => $productor,
            'ID_Categoria' => $this->cat('Hamacas de Algodón'),
            'Nombre_Producto' => 'Hamaca matrimonial',
            'Precio' => 1100,
            'Moneda' => 'mxn',
            'Etiquetas' => ['artesanal', 'demo'],
            'URLs_Imagenes' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Hamaca matrimonial')
            ->assertJsonPath('data.precio', '1100.00')
            ->assertJsonPath('data.moneda', 'MXN')
            ->assertJsonPath('data.subcategoria_id', $this->cat('Hamaquería'))
            ->assertJsonPath('data.tipo_id', $this->cat('Hamacas de Algodón'))
            ->assertJsonPath('data.etiquetas', ['artesanal', 'demo']);

        $imagenes = $response->json('data.imagenes');
        $this->assertCount(2, $imagenes);
        foreach ($imagenes as $url) {
            $this->assertStringStartsWith(rtrim(config('app.url'), '/').'/storage/productos/', $url);
        }

        // En BD: rutas relativas y JSON simple (sin doble codificación)
        $fila = DB::table('productos')->where('ID_Producto', $response->json('data.id'))->first();
        $rutas = json_decode($fila->URLs_Imagenes, true);
        $this->assertIsArray($rutas);
        foreach ($rutas as $ruta) {
            $this->assertStringStartsWith('productos/', $ruta);
            Storage::disk('public')->assertExists($ruta);
        }
        $this->assertSame(['artesanal', 'demo'], json_decode($fila->Etiquetas, true));
    }

    public function test_acepta_alias_snake_case(): void
    {
        [$user, $productor] = $this->dueno();
        Sanctum::actingAs($user);

        $this->postJson('/v1/productos', [
            'productor_id' => $productor, 'categoria_id' => $this->cat('Macetas'),
            'nombre' => 'Maceta', 'precio' => 99.5, 'inventario' => 4, 'disponible' => false,
        ])->assertCreated()
            ->assertJsonPath('data.nombre', 'Maceta')
            ->assertJsonPath('data.inventario', 4)
            ->assertJsonPath('data.disponible', false);
    }

    public function test_valida_categoria_asignable(): void
    {
        [$user, $productor] = $this->dueno();
        Sanctum::actingAs($user);
        $base = ['ID_Productor' => $productor, 'Nombre_Producto' => 'X'];

        // inexistente
        $this->postJson('/v1/productos', $base + ['ID_Categoria' => 99999])->assertStatus(422)->assertJsonValidationErrors('ID_Categoria');
        // raíz (demasiado general)
        $this->postJson('/v1/productos', $base + ['ID_Categoria' => $this->cat('Artesanías')])->assertStatus(422)->assertJsonValidationErrors('ID_Categoria');
        // inactiva
        DB::table('categorias_productos')->insert(['ID_Categoria' => 500, 'Nombre_Categoria' => 'Otro', 'slug' => 'otro', 'ID_Categoria_Padre' => $this->cat('Frutas'), 'activo' => 0]);
        $this->postJson('/v1/productos', $base + ['ID_Categoria' => 500])->assertStatus(422)->assertJsonValidationErrors('ID_Categoria');
        // subcategoría y tipo, OK
        $this->postJson('/v1/productos', $base + ['ID_Categoria' => $this->cat('Frutas')])->assertCreated();
        $this->postJson('/v1/productos', $base + ['ID_Categoria' => $this->cat('Patos')])->assertCreated();
    }

    public function test_valida_campos(): void
    {
        [$user, $productor] = $this->dueno();
        Sanctum::actingAs($user);

        $this->postJson('/v1/productos', ['ID_Productor' => $productor])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['ID_Categoria', 'Nombre_Producto']);

        $this->postJson('/v1/productos', [
            'ID_Productor' => $productor, 'ID_Categoria' => $this->cat('Frutas'),
            'Nombre_Producto' => 'X', 'Precio' => -1, 'Moneda' => 'PESOS',
        ])->assertStatus(422)->assertJsonValidationErrors(['Precio', 'Moneda']);
    }

    public function test_otro_usuario_no_puede_crear_ni_editar_ni_borrar(): void
    {
        [, $productor] = $this->dueno();
        $id = $this->crearProducto($productor, $this->cat('Frutas'));
        Sanctum::actingAs($this->crearUsuario());

        $this->postJson('/v1/productos', [
            'ID_Productor' => $productor, 'ID_Categoria' => $this->cat('Frutas'), 'Nombre_Producto' => 'X',
        ])->assertForbidden()->assertJsonPath('success', false);
        $this->putJson("/v1/productos/{$id}", ['Nombre_Producto' => 'Hack'])->assertForbidden();
        $this->deleteJson("/v1/productos/{$id}")->assertForbidden();

        $this->assertDatabaseHas('productos', ['ID_Producto' => $id]);
    }

    public function test_personal_de_gobierno_puede_gestionar_cualquier_producto(): void
    {
        [, $productor] = $this->dueno();
        $id = $this->crearProducto($productor, $this->cat('Frutas'));
        Sanctum::actingAs($this->crearUsuario(['Tipo_Usuario' => 'Admin_Gobierno']));

        $this->putJson("/v1/productos/{$id}", ['Nombre_Producto' => 'Editado por admin'])->assertOk();
        $this->deleteJson("/v1/productos/{$id}")->assertOk();
        $this->assertDatabaseMissing('productos', ['ID_Producto' => $id]);
    }

    public function test_el_dueno_actualiza_y_elimina(): void
    {
        [$user, $productor] = $this->dueno();
        $id = $this->crearProducto($productor, $this->cat('Frutas'), ['Nombre_Producto' => 'Viejo']);
        Sanctum::actingAs($user);

        $this->putJson("/v1/productos/{$id}", [
            'Nombre_Producto' => 'Nuevo', 'Precio' => 55, 'ID_Categoria' => $this->cat('Chiles'), 'Etiquetas' => ['a'],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Nuevo')
            ->assertJsonPath('data.precio', '55.00')
            ->assertJsonPath('data.categoria_id', $this->cat('Chiles'))
            ->assertJsonPath('data.etiquetas', ['a']);

        $this->deleteJson("/v1/productos/{$id}")->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('productos', ['ID_Producto' => $id]);
    }

    public function test_actualizar_o_borrar_inexistente_da_404(): void
    {
        Sanctum::actingAs($this->crearUsuario());

        $this->putJson('/v1/productos/9999', ['Nombre_Producto' => 'x'])->assertNotFound();
        $this->deleteJson('/v1/productos/9999')->assertNotFound();
    }
}
