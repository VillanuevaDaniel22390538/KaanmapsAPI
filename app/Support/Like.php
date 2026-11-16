<?php

namespace App\Support;

class Like
{
    /**
     * Patrón "contiene" con %, _ y ! del usuario escapados (usar con ESCAPE '!').
     * Válido en MySQL/MariaDB y sqlite.
     */
    public static function contiene(string $valor): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $valor).'%';
    }
}
