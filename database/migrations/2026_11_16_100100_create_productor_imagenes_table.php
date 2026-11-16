<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Imágenes de las instalaciones del productor (una fila por imagen).
 * Copia lo que hubiera en productores.imagenes_instalaciones (JSON, hoy deprecada;
 * NO se borra la columna vieja).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productor_imagenes', function (Blueprint $table) {
            $table->id();
            $table->integer('productor_id');
            $table->string('ruta', 255);
            $table->unsignedSmallInteger('orden')->default(0);

            $table->index(['productor_id', 'orden'], 'productor_imagenes_productor_orden_idx');
            $table->foreign('productor_id', 'productor_imagenes_productor_fk')
                ->references('ID_Productor')->on('productores')
                ->cascadeOnDelete();
        });

        DB::table('productores')
            ->whereNotNull('imagenes_instalaciones')
            ->orderBy('ID_Productor')
            ->each(function ($p) {
                $lista = json_decode($p->imagenes_instalaciones, true);

                if (! is_array($lista)) {
                    return;
                }

                $orden = 0;
                foreach ($lista as $ruta) {
                    if (! is_string($ruta) || trim($ruta) === '') {
                        continue;
                    }

                    $ruta = \App\Support\MediaUrl::paraAlmacenar($ruta);

                    DB::table('productor_imagenes')->updateOrInsert(
                        ['productor_id' => $p->ID_Productor, 'ruta' => $ruta],
                        ['orden' => $orden++]
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('productor_imagenes');
    }
};
