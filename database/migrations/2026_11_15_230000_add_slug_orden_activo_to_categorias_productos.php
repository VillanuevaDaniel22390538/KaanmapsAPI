<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias_productos', function (Blueprint $table) {
            $table->string('slug', 80)->nullable()->after('Nombre_Categoria');
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
        });

        // Backfill: slug estable para las filas existentes (ids bajos primero,
        // así los nombres "originales" conservan el slug limpio).
        $usados = [];
        $filas = DB::table('categorias_productos')->orderBy('ID_Categoria')->get(['ID_Categoria', 'Nombre_Categoria']);

        foreach ($filas as $fila) {
            $base = Str::slug($fila->Nombre_Categoria) ?: 'categoria';
            $slug = $base;

            if (isset($usados[$slug])) {
                $slug = $base.'-'.$fila->ID_Categoria;
            }

            $usados[$slug] = true;

            DB::table('categorias_productos')
                ->where('ID_Categoria', $fila->ID_Categoria)
                ->update(['slug' => $slug]);
        }

        Schema::table('categorias_productos', function (Blueprint $table) {
            $table->unique('slug', 'categorias_productos_slug_unique');
            $table->index(['activo', 'orden'], 'categorias_productos_activo_orden_idx');
        });
    }

    public function down(): void
    {
        Schema::table('categorias_productos', function (Blueprint $table) {
            $table->dropIndex('categorias_productos_activo_orden_idx');
            $table->dropUnique('categorias_productos_slug_unique');
        });

        Schema::table('categorias_productos', function (Blueprint $table) {
            $table->dropColumn(['slug', 'orden', 'activo']);
        });
    }
};
