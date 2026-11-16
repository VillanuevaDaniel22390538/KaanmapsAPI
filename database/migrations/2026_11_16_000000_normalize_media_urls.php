<?php

use App\Support\MediaUrl;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de DATOS: convierte URLs absolutas locales ("http://localhost:8001/storage/x.png")
 * y rutas "/storage/x.png" en la ruta relativa al disco ("x.png").
 *
 * - Solo toca URLs que apuntan a /storage/ en un host local/de APP_URL.
 * - Deja intactas las URLs externas (Google) y los "assets/..." de la app.
 * - Guarda el valor original en `media_url_backups`; down() lo restaura exacto.
 * - Es segura de repetir: lo que ya es relativo no se toca.
 */
return new class extends Migration
{
    /** tabla => [pk, columna, es_json_lista] */
    private array $objetivos = [
        ['usuarios', 'ID_Usuario', 'Avatar_URL', false],
        ['productores', 'ID_Productor', 'Logo_URL', false],
        ['productos', 'ID_Producto', 'URLs_Imagenes', true],
        ['categorias_productos', 'ID_Categoria', 'URL_Icono', false],
    ];

    public function up(): void
    {
        Schema::create('media_url_backups', function (Blueprint $table) {
            $table->id();
            $table->string('tabla', 64);
            $table->string('pk_columna', 64);
            $table->string('pk_valor', 64);
            $table->string('columna', 64);
            $table->longText('valor_original')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->index(['tabla', 'columna']);
        });

        foreach ($this->objetivos as [$tabla, $pk, $columna, $esLista]) {
            DB::table($tabla)
                ->whereNotNull($columna)
                ->orderBy($pk)
                ->chunkById(200, function ($filas) use ($tabla, $pk, $columna, $esLista) {
                    foreach ($filas as $fila) {
                        $original = $fila->{$columna};
                        $nuevo = $this->normalizar($original, $esLista);

                        if ($nuevo === $original) {
                            continue;
                        }

                        DB::table('media_url_backups')->insert([
                            'tabla' => $tabla,
                            'pk_columna' => $pk,
                            'pk_valor' => (string) $fila->{$pk},
                            'columna' => $columna,
                            'valor_original' => $original,
                            'creado_en' => now(),
                        ]);

                        DB::table($tabla)->where($pk, $fila->{$pk})->update([$columna => $nuevo]);
                    }
                }, $pk);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('media_url_backups')) {
            DB::table('media_url_backups')->orderBy('id')->each(function ($b) {
                DB::table($b->tabla)->where($b->pk_columna, $b->pk_valor)->update([$b->columna => $b->valor_original]);
            });
        }

        Schema::dropIfExists('media_url_backups');
    }

    private function normalizar(?string $valor, bool $esLista): ?string
    {
        if ($valor === null || $valor === '') {
            return $valor;
        }

        if (! $esLista) {
            return MediaUrl::paraAlmacenar($valor);
        }

        $lista = json_decode($valor, true);

        if (! is_array($lista)) {
            return $valor; // formato desconocido: no se toca
        }

        $nueva = array_map(
            fn ($v) => is_string($v) ? MediaUrl::paraAlmacenar($v) : $v,
            $lista
        );

        return $nueva === $lista ? $valor : json_encode($nueva, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
};
