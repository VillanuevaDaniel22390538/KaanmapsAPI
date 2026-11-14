<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Convocatoria extends Model
{
    protected $table = 'convocatorias';
    protected $primaryKey = 'ID_Convocatoria';
    public $timestamps = false;

    protected $fillable = [
        'ID_Dependencia', 'ID_Evento', 'Titulo', 'Descripcion', 'Tipo',
        'Fecha_Inicio', 'Fecha_Fin', 'Fecha_Evento', 'Requisitos', 'Beneficios',
        'Documentos_Requeridos', 'Cupo_Maximo', 'Estado', 'URL_Imagen', 'Creada_Por'
    ];

    protected $casts = [
        'Documentos_Requeridos' => 'array',
        'Fecha_Inicio' => 'datetime',
        'Fecha_Fin' => 'datetime',
        'Fecha_Evento' => 'datetime',
    ];

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'ID_Evento', 'ID_Evento');
    }
}