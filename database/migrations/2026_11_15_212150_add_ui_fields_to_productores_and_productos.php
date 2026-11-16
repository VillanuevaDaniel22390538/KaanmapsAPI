<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RECONSTRUIDA a partir del esquema real de kaanmapsdb.
 *
 * La migración original ya estaba aplicada (batch 2) pero su archivo no estaba
 * en el repo. Esta versión usa guardas hasColumn: en la BD real no hace nada;
 * en una BD nueva crea las mismas columnas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productores', function (Blueprint $table) {
            if (! Schema::hasColumn('productores', 'categoria_id')) {
                $table->unsignedBigInteger('categoria_id')->nullable();
            }
            if (! Schema::hasColumn('productores', 'email_contacto')) {
                $table->string('email_contacto', 255)->nullable();
            }
            if (! Schema::hasColumn('productores', 'facebook')) {
                $table->string('facebook', 255)->nullable();
            }
            if (! Schema::hasColumn('productores', 'instagram')) {
                $table->string('instagram', 255)->nullable();
            }
            if (! Schema::hasColumn('productores', 'hora_apertura')) {
                $table->time('hora_apertura')->nullable();
            }
            if (! Schema::hasColumn('productores', 'hora_cierre')) {
                $table->time('hora_cierre')->nullable();
            }
            if (! Schema::hasColumn('productores', 'imagenes_instalaciones')) {
                $table->longText('imagenes_instalaciones')->nullable();
            }
        });

        Schema::table('productos', function (Blueprint $table) {
            if (! Schema::hasColumn('productos', 'subcategoria_id')) {
                $table->unsignedBigInteger('subcategoria_id')->nullable();
            }
            if (! Schema::hasColumn('productos', 'tipos_id')) {
                $table->longText('tipos_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            foreach (['subcategoria_id', 'tipos_id'] as $col) {
                if (Schema::hasColumn('productos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('productores', function (Blueprint $table) {
            foreach (['categoria_id', 'email_contacto', 'facebook', 'instagram', 'hora_apertura', 'hora_cierre', 'imagenes_instalaciones'] as $col) {
                if (Schema::hasColumn('productores', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
