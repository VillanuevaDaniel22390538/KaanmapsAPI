<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productores', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->string('email_contacto')->nullable();
            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
            $table->time('hora_apertura')->nullable();
            $table->time('hora_cierre')->nullable();
            $table->json('imagenes_instalaciones')->nullable();
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedBigInteger('subcategoria_id')->nullable();
            $table->json('tipos_id')->nullable()->after('subcategoria_id'); // Ejemplo: [1, 2]
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productores', function (Blueprint $table) {
            $table->dropColumn([
                'categoria_id',
                'email_contacto',
                'facebook',
                'instagram',
                'hora_apertura',
                'hora_cierre',
                'imagenes_instalaciones',
            ]);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn([
                'subcategoria_id',
                'tipos_id',
            ]);
        });
    }
};
