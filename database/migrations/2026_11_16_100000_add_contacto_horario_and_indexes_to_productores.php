<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * productores:
 *  - telefono_contacto (público del negocio, separado de usuarios.Telefono) y horario (texto libre).
 *  - categoria_id (se expone como categoria_principal_id): índice + FK a categorias_productos.
 *    La columna nació como BIGINT UNSIGNED; para poder tener FK contra ID_Categoria (INT) se
 *    cambia a INT nullable. Los valores existentes (1,2,3,5) caben sin problema.
 *  - índice (Estado, Estado_Verificacion) para el listado público.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('productores', function (Blueprint $table) {
            $table->string('telefono_contacto', 20)->nullable();
            $table->string('horario', 150)->nullable();
        });

        // Valores huérfanos romperían la FK: se anulan (no se borran filas).
        DB::table('productores')
            ->whereNotNull('categoria_id')
            ->whereNotIn('categoria_id', DB::table('categorias_productos')->select('ID_Categoria'))
            ->update(['categoria_id' => null]);

        if (! $sqlite) {
            Schema::table('productores', function (Blueprint $table) {
                $table->integer('categoria_id')->nullable()->change();
            });
        }

        Schema::table('productores', function (Blueprint $table) use ($sqlite) {
            $table->index('categoria_id', 'productores_categoria_id_idx');
            $table->index(['Estado', 'Estado_Verificacion'], 'productores_estado_verificacion_idx');

            if (! $sqlite) {
                $table->foreign('categoria_id', 'productores_categoria_id_fk')
                    ->references('ID_Categoria')->on('categorias_productos')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        $sqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('productores', function (Blueprint $table) use ($sqlite) {
            if (! $sqlite) {
                $table->dropForeign('productores_categoria_id_fk');
            }
            $table->dropIndex('productores_estado_verificacion_idx');
            $table->dropIndex('productores_categoria_id_idx');
        });

        if (! $sqlite) {
            Schema::table('productores', function (Blueprint $table) {
                $table->unsignedBigInteger('categoria_id')->nullable()->change();
            });
        }

        Schema::table('productores', function (Blueprint $table) {
            $table->dropColumn(['telefono_contacto', 'horario']);
        });
    }
};
