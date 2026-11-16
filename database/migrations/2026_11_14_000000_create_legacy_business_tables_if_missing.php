<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Esquema base "legacy" (tablas Pascal_Snake creadas fuera de Laravel).
 *
 * Cada tabla se crea SOLO si no existe, así que en la BD real (kaanmapsdb)
 * esta migración no hace nada. Existe para que una BD vacía (tests con sqlite,
 * un entorno nuevo) pueda levantar el esquema completo con `php artisan migrate`.
 *
 * Solo incluye las columnas originales; las columnas añadidas después las
 * agregan las migraciones posteriores.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categorias_productos')) {
            Schema::create('categorias_productos', function (Blueprint $table) {
                $table->increments('ID_Categoria');
                $table->string('Nombre_Categoria', 50)->unique();
                $table->integer('ID_Categoria_Padre')->nullable()->index();
                $table->text('Descripcion')->nullable();
                $table->string('URL_Icono', 255)->nullable();
                $table->string('Color_Hex', 7)->nullable();
            });
        }

        if (! Schema::hasTable('usuarios')) {
            Schema::create('usuarios', function (Blueprint $table) {
                $table->increments('ID_Usuario');
                $table->string('Nombre_Usuario', 50)->unique();
                $table->string('Email', 100)->unique();
                $table->boolean('Email_Verificado')->nullable()->default(false);
                $table->string('Contraseña_Hash', 255);
                $table->string('Tipo_Usuario', 30)->default('Invitado');
                $table->integer('ID_Dependencia')->nullable();
                $table->string('Nombre_Mostrado', 100)->nullable();
                $table->string('Telefono', 20)->nullable();
                $table->string('Avatar_URL', 255)->nullable();
                $table->dateTime('Fecha_Creacion')->nullable();
                $table->dateTime('Fecha_Actualizacion')->nullable();
                $table->dateTime('Ultimo_Acceso')->nullable();
                $table->string('Estado', 20)->nullable()->default('Activo');
                $table->string('Metodo_Registro', 20)->nullable()->default('Correo');
                $table->integer('ID_SuperAdmin_Creador')->nullable();
            });
        }

        if (! Schema::hasTable('productores')) {
            Schema::create('productores', function (Blueprint $table) {
                $table->increments('ID_Productor');
                $table->integer('ID_Usuario')->nullable()->index();
                $table->string('Nombre_Negocio', 100);
                $table->text('Descripcion_Negocio')->nullable();
                $table->string('Tipo_Actividad', 30)->nullable();
                $table->string('ID_Gubernamental', 50)->nullable();
                $table->string('Programa_Apoyo', 100)->nullable();
                $table->boolean('Certificacion_Verde')->nullable()->default(false);
                $table->string('Logo_URL', 255)->nullable();
                $table->string('Slogan', 200)->nullable();
                $table->decimal('Calificacion_Global', 2, 1)->nullable()->default(0);
                $table->date('Fecha_Registro')->nullable();
                $table->string('Estado_Verificacion', 20)->nullable()->default('Pendiente');
                $table->string('Estado', 20)->nullable()->default('Activo');
                $table->integer('ID_Dependencia_Registradora')->nullable();
                $table->integer('ID_Usuario_Registrador')->nullable();
                $table->string('Estado_Aprobacion_Gobierno', 20)->nullable()->default('Pendiente');
                $table->dateTime('Fecha_Aprobacion')->nullable();
                $table->integer('ID_Admin_Aprobador')->nullable();
            });
        }

        if (! Schema::hasTable('productos')) {
            Schema::create('productos', function (Blueprint $table) {
                $table->increments('ID_Producto');
                $table->integer('ID_Productor')->index();
                $table->integer('ID_Categoria')->index();
                $table->string('Nombre_Producto', 100);
                $table->text('Descripcion')->nullable();
                $table->decimal('Precio', 10, 2)->nullable();
                $table->string('Moneda', 3)->nullable()->default('MXN');
                $table->string('Tipo_Unidad', 20)->nullable();
                $table->integer('Cantidad_Inventario')->nullable()->default(0);
                $table->boolean('Disponible')->nullable()->default(true);
                $table->longText('URLs_Imagenes')->nullable();
                $table->longText('Etiquetas')->nullable();
                $table->date('Fecha_Registro')->nullable();
            });
        }

        if (! Schema::hasTable('ubicaciones_productores')) {
            Schema::create('ubicaciones_productores', function (Blueprint $table) {
                $table->increments('ID_Ubicacion');
                $table->integer('ID_Productor')->index();
                $table->decimal('Latitud', 10, 6);
                $table->decimal('Longitud', 10, 6);
                $table->string('Direccion', 255)->nullable();
                $table->string('Localidad', 100)->nullable();
                $table->string('Municipio', 100)->nullable();
                $table->string('Estado', 50)->nullable()->default('Quintana Roo');
                $table->string('Pais', 50)->nullable()->default('México');
                $table->string('Precision_Geolocalizacion', 20)->nullable();
                $table->boolean('Es_Principal')->nullable()->default(false);
            });
        }
    }

    /**
     * No-op a propósito: estas tablas no son propiedad de Laravel y nunca
     * deben borrarse con un rollback.
     */
    public function down(): void
    {
        //
    }
};
