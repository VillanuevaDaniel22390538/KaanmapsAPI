<?php

namespace App\Models;

use App\Support\CategoriaTree;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaProducto extends Model
{
    use HasFactory;

    protected $table = 'categorias_productos';

    protected $primaryKey = 'ID_Categoria';

    public $timestamps = false;

    protected $fillable = [
        'Nombre_Categoria',
        'slug',
        'ID_Categoria_Padre',
        'Descripcion',
        'URL_Icono',
        'Color_Hex',
        'orden',
        'activo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        // El árbol se cachea; cualquier cambio lo invalida.
        static::saved(fn () => CategoriaTree::flush());
        static::deleted(fn () => CategoriaTree::flush());
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'ID_Categoria', 'ID_Categoria');
    }

    public function padre()
    {
        return $this->belongsTo(CategoriaProducto::class, 'ID_Categoria_Padre', 'ID_Categoria');
    }

    public function subcategorias()
    {
        return $this->hasMany(CategoriaProducto::class, 'ID_Categoria_Padre', 'ID_Categoria');
    }
}
