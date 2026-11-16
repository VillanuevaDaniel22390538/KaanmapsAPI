<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    protected $primaryKey = 'ID_Producto';

    public $timestamps = false;

    protected $fillable = [
        'ID_Productor', 'ID_Categoria', 'ID_Subcategoria', 'ID_Subtipo', 'Nombre_Producto', 'Descripcion',
        'Precio', 'Moneda', 'Tipo_Unidad', 'Cantidad_Inventario', 'Disponible',
        'URLs_Imagenes', 'Etiquetas',
    ];

    protected $casts = [
        'URLs_Imagenes' => 'array',
        'Etiquetas' => 'array',
        'Disponible' => 'boolean',
    ];

    public function productor()
    {
        return $this->belongsTo(Productor::class, 'ID_Productor', 'ID_Productor');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaProducto::class, 'ID_Categoria', 'ID_Categoria');
    }
}
