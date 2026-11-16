<?php

namespace App\Rules;

use App\Support\CategoriaTree;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Una categoría asignable a un producto: existe, está activa y es el nodo
 * específico (subcategoría o tipo), no una categoría raíz.
 */
class CategoriaAsignable implements ValidationRule
{
    /** @param int $minNivel 2 = subcategoría o tipo (productos); 1 = cualquier nivel (categoría principal de un productor) */
    public function __construct(private int $minNivel = 2)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tree = CategoriaTree::current();

        if (! is_numeric($value) || ! $tree->has((int) $value)) {
            $fail('La categoría seleccionada no existe.');

            return;
        }

        $id = (int) $value;

        if (! $tree->estaActiva($id)) {
            $fail('La categoría seleccionada ya no está disponible.');

            return;
        }

        if ($tree->nivel($id) < $this->minNivel) {
            $fail('Selecciona una subcategoría o tipo, no una categoría general.');
        }
    }
}
