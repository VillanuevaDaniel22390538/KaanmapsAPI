<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UbicacionProductor extends Model
{
    protected $table = 'ubicaciones_productores';
    protected $primaryKey = 'ID_Ubicacion';
    public $timestamps = false;

    protected $fillable = [
        'ID_Productor', 'Latitud', 'Longitud', 'Direccion', 'Localidad',
        'Municipio', 'Estado', 'Pais', 'Precision_Geolocalizacion', 'Es_Principal'
    ];
}