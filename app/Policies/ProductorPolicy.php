<?php

namespace App\Policies;

use App\Models\Productor;
use App\Models\User;
use App\Policies\Concerns\ChecksStaff;

class ProductorPolicy
{
    use ChecksStaff;

    /** Un usuario solo crea su propio negocio; el personal puede crear para otros. */
    public function create(User $user, ?int $usuarioId = null): bool
    {
        return $this->esPersonal($user) || ($usuarioId !== null && $usuarioId === (int) $user->ID_Usuario);
    }

    public function update(User $user, Productor $productor): bool
    {
        return $this->esPersonal($user)
            || ($productor->ID_Usuario !== null && (int) $productor->ID_Usuario === (int) $user->ID_Usuario);
    }

    /** Borrar: dueño o administradores (no los capturistas). */
    public function delete(User $user, Productor $productor): bool
    {
        return $this->esAdministrador($user)
            || ($productor->ID_Usuario !== null && (int) $productor->ID_Usuario === (int) $user->ID_Usuario);
    }
}
