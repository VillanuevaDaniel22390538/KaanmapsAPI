<?php

namespace App\Models;

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
        'ID_Categoria',
        'Nombre_Negocio',
        'Descripcion_Negocio',
        'Tipo_Actividad',
        'ID_Gubernamental',
        'Programa_Apoyo',
        'Certificacion_Verde',
        'Logo_URL',
        'Imagen_Portada_URL',
        'Imagenes_Instalaciones',
        'Slogan',
        'Estado_Verificacion',
        'Estado',
        'ID_Dependencia_Registradora',
        'ID_Usuario_Registrador',
        'Estado_Aprobacion_Gobierno',
    ];

    protected $casts = [
        'Imagenes_Instalaciones' => 'array',
    ];

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
}
