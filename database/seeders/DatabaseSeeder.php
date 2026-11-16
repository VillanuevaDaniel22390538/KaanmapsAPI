<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Todos los seeders son idempotentes (updateOrCreate): se pueden correr varias
 * veces sin duplicar datos. Se ejecutan en este orden por dependencias.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategoriaSeeder::class,
            ReasignarCategoriasProductosSeeder::class,
        ]);

        // Datos de demostración: nunca en producción.
        if (! app()->environment('production')) {
            $this->call(ProductosDemoSeeder::class);
        }
    }
}
