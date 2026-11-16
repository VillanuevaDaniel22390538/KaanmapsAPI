<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaBaselineTest extends TestCase
{
    public function test_el_esquema_base_se_levanta_en_una_bd_vacia(): void
    {
        foreach (['categorias_productos', 'productos', 'productores', 'ubicaciones_productores', 'usuarios'] as $tabla) {
            $this->assertTrue(Schema::hasTable($tabla), "Falta la tabla {$tabla}");
        }

        foreach (['categoria_id', 'email_contacto', 'facebook', 'instagram', 'imagenes_instalaciones'] as $col) {
            $this->assertTrue(Schema::hasColumn('productores', $col), "Falta productores.{$col}");
        }

        $this->assertTrue(Schema::hasColumn('productos', 'subcategoria_id'));
    }
}
