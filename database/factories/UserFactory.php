<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Factory para la tabla legacy `usuarios` (columnas Pascal_Snake).
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'Nombre_Usuario' => fake()->unique()->userName(),
            'Email' => fake()->unique()->safeEmail(),
            'Contraseña_Hash' => static::$password ??= Hash::make('password'),
            'Tipo_Usuario' => 'Productor',
            'Telefono' => fake()->numerify('998#######'),
            'Estado' => 'Activo',
        ];
    }
}
