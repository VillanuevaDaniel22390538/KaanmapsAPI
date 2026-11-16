<?php

namespace Tests\Feature;

use App\Models\ProductorImagen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsCatalog;
use Tests\TestCase;

class ProductorSchemaTest extends TestCase
{
    use BuildsCatalog;

    public function test_existen_las_columnas_y_tablas_nuevas(): void
    {
        $this->assertTrue(Schema::hasColumns('productores', ['telefono_contacto', 'horario', 'categoria_id']));
        $this->assertTrue(Schema::hasTable('productor_imagenes'));
        $this->assertTrue(Schema::hasColumns('productor_imagenes', ['id', 'productor_id', 'ruta', 'orden']));
    }

    public function test_las_imagenes_se_borran_en_cascada_con_el_productor(): void
    {
        $productor = $this->crearProductor();
        ProductorImagen::create(['productor_id' => $productor, 'ruta' => 'instalaciones/a.jpg', 'orden' => 1]);

        DB::table('productores')->where('ID_Productor', $productor)->delete();

        $this->assertSame(0, ProductorImagen::count());
    }

    public function test_la_migracion_copia_imagenes_viejas_sin_duplicar_y_es_reversible(): void
    {
        $migracion = require base_path('database/migrations/2026_11_16_100100_create_productor_imagenes_table.php');
        $migracion->down();
        $this->assertFalse(Schema::hasTable('productor_imagenes'));

        $productor = $this->crearProductor([
            'imagenes_instalaciones' => json_encode(['instalaciones/a.jpg', 'http://localhost:8001/storage/instalaciones/b.jpg']),
        ]);

        $migracion->up();
        $migracion->down();
        $migracion->up(); // repetir no duplica

        $rutas = ProductorImagen::where('productor_id', $productor)->orderBy('orden')->pluck('ruta')->all();
        $this->assertSame(['instalaciones/a.jpg', 'instalaciones/b.jpg'], $rutas);

        // La columna vieja NO se toca
        $this->assertNotNull(DB::table('productores')->where('ID_Productor', $productor)->value('imagenes_instalaciones'));
    }

    public function test_la_migracion_de_productores_es_reversible(): void
    {
        $migracion = require base_path('database/migrations/2026_11_16_100000_add_contacto_horario_and_indexes_to_productores.php');

        // productor_imagenes depende de productores; la FK de sqlite no lo impide al quitar columnas
        $migracion->down();
        $this->assertFalse(Schema::hasColumn('productores', 'horario'));
        $this->assertFalse(Schema::hasColumn('productores', 'telefono_contacto'));

        $migracion->up();
        $this->assertTrue(Schema::hasColumn('productores', 'horario'));
    }
}
