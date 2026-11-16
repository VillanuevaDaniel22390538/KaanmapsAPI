<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\Productor;
use App\Models\User;
use App\Policies\Concerns\ChecksStaff;

class ProductoPolicy
{
    use ChecksStaff;

    /** Crear un producto en el productor indicado. */
    public function create(User $user, ?int $productorId = null): bool
    {
        if ($this->esPersonal($user)) {
            return true;
        }

        return $productorId !== null
            && Productor::where('ID_Productor', $productorId)
                ->where('ID_Usuario', $user->ID_Usuario)
                ->exists();
    }

    public function update(User $user, Producto $producto): bool
    {
        return $this->create($user, (int) $producto->ID_Productor);
    }

    public function delete(User $user, Producto $producto): bool
    {
        return $this->update($user, $producto);
    }
}
