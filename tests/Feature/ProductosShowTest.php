<?php

namespace Tests\Feature;

use Tests\Concerns\BuildsCatalog;
use Tests\Concerns\UsesCategorias;
use Tests\TestCase;

class ProductosShowTest extends TestCase
{
    use BuildsCatalog, UsesCategorias;

    public function test_devuelve_un_producto(): void
    {
        $this->sembrarCategorias();
        $productor = $this->crearProductor();
        $id = $this->crearProducto($productor, $this->cat('Chiles'), ['Nombre_Producto' => 'Habanero', 'Precio' => '70']);

        $this->getJson("/v1/productos/{$id}")->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.nombre', 'Habanero')
            ->assertJsonPath('data.precio', '70.00')
            ->assertJsonPath('data.subcategoria_id', $this->cat('Chiles'))
            ->assertJsonPath('data.categoria_raiz_id', $this->cat('Agricultura'));
    }

    public function test_404_si_no_existe_o_el_id_no_es_numerico_o_el_productor_no_es_visible(): void
    {
        $this->sembrarCategorias();
        $this->getJson('/v1/productos/9999')->assertNotFound()->assertJsonPath('success', false);
        $this->getJson('/v1/productos/abc')->assertNotFound();

        $oculto = $this->crearProductor(['Estado' => 'Inactivo']);
        $id = $this->crearProducto($oculto, $this->cat('Frutas'));
        $this->getJson("/v1/productos/{$id}")->assertNotFound();
    }
}
