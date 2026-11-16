<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductorImagen extends Model
{
    protected $table = 'productor_imagenes';

    public $timestamps = false;

    protected $fillable = ['productor_id', 'ruta', 'orden'];

    protected $casts = ['orden' => 'integer'];

    public function productor()
    {
        return $this->belongsTo(Productor::class, 'productor_id', 'ID_Productor');
    }
}
