<?php

namespace Tests\Feature;

use Tests\Concerns\BuildsCatalog;
use Tests\Concerns\UsesCategorias;
use Tests\TestCase;

class ProductoresShowTest extends TestCase
{
    use BuildsCatalog, UsesCategorias;

    public function test_devuelve_el_detalle_sin_productos_por_defecto(): void
    {
        $this->sembrarCategorias();
        $id = $this->crearProductor(['Nombre_Negocio' => 'Miel Maya']);
        $this->crearUbicacion($id);
        $this->crearProducto($id, $this->cat('Miel de Abeja'));

        $res = $this->getJson("/v1/productores/{$id}")->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.nombre_negocio', 'Miel Maya')
            ->assertJsonCount(1, 'data.ubicaciones');

        $this->assertArrayNotHasKey('productos', $res->json('data'));
    }

    public function test_include_productos_devuelve_productos_con_su_resource(): void
    {
        $this->sembrarCategorias();
        $id = $this->crearProductor();
        $this->crearProducto($id, $this->cat('Guayaberas'), ['Nombre_Producto' => 'Guayabera', 'Precio' => '1450']);

        $p = $this->getJson("/v1/productores/{$id}?include=productos")->assertOk()->json('data.productos.0');

        $this->assertSame('Guayabera', $p['nombre']);
        $this->assertSame('1450.00', $p['precio']);
        $this->assertSame($this->cat('Guayaberas'), $p['tipo_id']);
        $this->assertArrayNotHasKey('Nombre_Producto', $p);

        $this->getJson("/v1/productores/{$id}?include=otra")->assertStatus(422);
    }

    public function test_404_si_no_existe_no_es_numerico_o_no_es_visible(): void
    {
        $this->getJson('/v1/productores/9999')->assertNotFound()->assertJsonPath('success', false);
        $this->getJson('/v1/productores/abc')->assertNotFound();

        $inactivo = $this->crearProductor(['Estado' => 'Inactivo']);
        $this->getJson("/v1/productores/{$inactivo}")->assertNotFound();

        $pendiente = $this->crearProductor(['Estado_Verificacion' => 'Pendiente']);
        $this->getJson("/v1/productores/{$pendiente}")->assertOk();
        config(['catalog.only_verified' => true]);
        $this->getJson("/v1/productores/{$pendiente}")->assertNotFound();
    }
}
