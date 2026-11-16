<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices para los filtros/orden de GET /productos.
 * (ID_Categoria e ID_Productor ya tienen índice por sus FKs.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->index(['Disponible', 'Fecha_Registro'], 'productos_disponible_fecha_idx');
            $table->index('Precio', 'productos_precio_idx');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('productos_precio_idx');
            $table->dropIndex('productos_disponible_fecha_idx');
        });
    }
};
