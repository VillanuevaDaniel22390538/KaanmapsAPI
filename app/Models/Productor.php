<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Productor extends Model
{
    use HasFactory;

    protected $table = 'productores';

    protected $primaryKey = 'ID_Productor';

    public $timestamps = false;

    protected $fillable = [
        'ID_Usuario',
        'Nombre_Negocio',
        'Descripcion_Negocio',
        'Tipo_Actividad',
        'ID_Gubernamental',
        'Programa_Apoyo',
        'Certificacion_Verde',
        'Logo_URL',
        'Slogan',
        'Estado_Verificacion',
        'Estado',
        'ID_Dependencia_Registradora',
        'ID_Usuario_Registrador',
        'Estado_Aprobacion_Gobierno',
        // Campos añadidos para la UI (categoria_id = categoría principal)
        'categoria_id',
        'email_contacto',
        'facebook',
        'instagram',
        'telefono_contacto',
        'horario',
    ];

    /**
     * Productores visibles en el catálogo público (ver config/catalog.php).
     */
    public function scopeCatalogo(Builder $query): Builder
    {
        $query->where('Estado', 'Activo');

        if (config('catalog.only_verified')) {
            $query->where('Estado_Verificacion', 'Verificado');
        }

        return $query;
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'ID_Usuario', 'ID_Usuario');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'ID_Productor', 'ID_Productor');
    }

    public function ubicaciones()
    {
        return $this->hasMany(UbicacionProductor::class, 'ID_Productor', 'ID_Productor');
    }

    /** Imágenes de instalaciones (tabla productor_imagenes), ya ordenadas. */
    public function imagenes()
    {
        return $this->hasMany(ProductorImagen::class, 'productor_id', 'ID_Productor')
            ->orderBy('orden')
            ->orderBy('id');
    }

    public function categoriaPrincipal()
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id', 'ID_Categoria');
    }
}
