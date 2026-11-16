<?php

namespace Tests\Feature;

use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsCatalog;
use Tests\TestCase;

class UbicacionesTest extends TestCase
{
    use BuildsCatalog;

    public function test_get_ubicaciones_requiere_token(): void
    {
        $this->getJson('/v1/ubicaciones?productor_id=1')->assertUnauthorized();
        $this->postJson('/v1/ubicaciones', [])->assertUnauthorized();
    }

    public function test_lista_ubicaciones_en_snake_case_con_coordenadas_float(): void
    {
        Sanctum::actingAs($this->crearUsuario());
        $productor = $this->crearProductor();
        $this->crearUbicacion($productor, ['Latitud' => '21.141179', 'Longitud' => '-86.864976', 'Es_Principal' => 1]);

        $json = $this->getJson("/v1/ubicaciones?productor_id={$productor}")->assertOk()->json('data.0');

        $this->assertSame([
            'id', 'productor_id', 'latitud', 'longitud', 'direccion', 'localidad', 'municipio',
            'estado', 'pais', 'precision_geolocalizacion', 'es_principal',
        ], array_keys($json));
        $this->assertIsFloat($json['latitud']);
        $this->assertIsFloat($json['longitud']);
        $this->assertSame(21.141179, $json['latitud']);
        $this->assertSame(-86.864976, $json['longitud']);
        $this->assertTrue($json['es_principal']);
        $this->assertSame($productor, $json['productor_id']);
    }

    public function test_crea_una_ubicacion_con_nombres_legacy(): void
    {
        Sanctum::actingAs($this->crearUsuario());
        $productor = $this->crearProductor();

        $this->postJson('/v1/ubicaciones', [
            'ID_Productor' => $productor, 'Latitud' => 18.5, 'Longitud' => -88.3, 'Municipio' => 'Bacalar',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.municipio', 'Bacalar')
            ->assertJsonPath('data.latitud', 18.5);

        $this->assertDatabaseHas('ubicaciones_productores', ['ID_Productor' => $productor, 'Municipio' => 'Bacalar']);
    }

    public function test_crea_una_ubicacion_con_alias_snake_case(): void
    {
        Sanctum::actingAs($this->crearUsuario());
        $productor = $this->crearProductor();

        $this->postJson('/v1/ubicaciones', [
            'productor_id' => $productor, 'latitud' => 20.2, 'longitud' => -87.4, 'es_principal' => true,
        ])->assertCreated()->assertJsonPath('data.es_principal', true);
    }

    public function test_valida_coordenadas_y_productor(): void
    {
        Sanctum::actingAs($this->crearUsuario());

        $this->postJson('/v1/ubicaciones', ['ID_Productor' => 999, 'Latitud' => 200, 'Longitud' => 'x'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['ID_Productor', 'Latitud', 'Longitud']]);

        $this->getJson('/v1/ubicaciones')->assertStatus(422);
    }
}
