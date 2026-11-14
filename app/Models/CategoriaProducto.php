<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaProducto extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la base de datos.
     */
    protected $table = 'categorias_productos';

    /**
     * Llave primaria personalizada.
     */
    protected $primaryKey = 'ID_Categoria';

    /**
     * La tabla no tiene columnas created_at y updated_at.
     */
    public $timestamps = false;

    /**
     * Campos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'Nombre_Categoria',
        'ID_Categoria_Padre',
        'Descripcion',
        'URL_Icono',
        'Color_Hex',
    ];

    /**
     * Relación: Una categoría tiene muchos productos.
     * (Opcional, pero útil para consultas futuras)
     */
    public function productos()
    {
        return $this->hasMany(Producto::class, 'ID_Categoria', 'ID_Categoria');
    }

    /**
     * Relación: Una categoría puede tener subcategorías.
     */
    public function subcategorias()
    {
        return $this->hasMany(CategoriaProducto::class, 'ID_Categoria_Padre', 'ID_Categoria');
    }
}