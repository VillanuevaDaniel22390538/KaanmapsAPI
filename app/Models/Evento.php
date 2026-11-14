<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    protected $table = 'eventos';
    protected $primaryKey = 'ID_Evento';
    public $timestamps = false;

    protected $fillable = [
        'Nombre_Evento', 'Descripcion', 'ID_Organizador', 'ID_Dependencia_Organizadora',
        'Fecha_Inicio', 'Fecha_Fin', 'Ubicacion', 'Latitud', 'Longitud',
        'Tipo_Evento', 'Imagen_Portada', 'Estado', 'Estado_Aprobacion'
    ];

    public function convocatorias()
    {
        return $this->hasMany(Convocatoria::class, 'ID_Evento', 'ID_Evento');
    }
}