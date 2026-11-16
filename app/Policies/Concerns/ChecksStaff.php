<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksStaff
{
    /** Personal con permiso para gestionar cualquier productor/producto. */
    protected function esPersonal(User $user): bool
    {
        return in_array($user->Tipo_Usuario, ['SuperAdmin', 'Admin_Gobierno', 'Capturista_Gobierno'], true);
    }

    /** Solo administradores pueden borrar recursos ajenos. */
    protected function esAdministrador(User $user): bool
    {
        return in_array($user->Tipo_Usuario, ['SuperAdmin', 'Admin_Gobierno'], true);
    }
}
