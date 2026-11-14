<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'ID_Usuario';
    public $timestamps = false;

    protected $fillable = [
        'Nombre_Usuario',
        'Email',
        'Contraseña_Hash',
        'Tipo_Usuario',
        'ID_Dependencia',
        'Nombre_Mostrado',
        'Telefono',
        'Avatar_URL',
        'Estado',
        'Metodo_Registro',
    ];

    protected $hidden = [
        'Contraseña_Hash',
    ];

    // Indicar a Laravel que la contraseña se llama diferente
    public function getAuthPassword()
    {
        return $this->Contraseña_Hash;
    }

    public function productor()
    {
        return $this->hasOne(Productor::class, 'ID_Usuario', 'ID_Usuario');
    }
}