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
            // Se registran en las fases siguientes:
            // ReasignarCategoriasProductosSeeder::class,
            // ProductosDemoSeeder::class,
        ]);
    }
}
