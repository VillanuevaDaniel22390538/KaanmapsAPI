<?php

namespace App\Http\Requests\Concerns;

/**
 * Permite que los endpoints de escritura acepten nombres snake_case minúscula
 * (los mismos de las respuestas) además de los nombres legacy Pascal_Snake.
 * Si llegan ambos, gana el legacy (no rompe a los clientes actuales).
 */
trait AcceptsSnakeCaseAliases
{
    /** @return array<string, string> alias => nombre legacy */
    abstract protected function inputAliases(): array;

    protected function prepareForValidation(): void
    {
        $all = $this->all();
        $merge = [];

        foreach ($this->inputAliases() as $alias => $legacy) {
            if (array_key_exists($alias, $all) && ! array_key_exists($legacy, $all)) {
                $merge[$legacy] = $all[$alias];
            }
        }

        if ($merge) {
            $this->merge($merge);
        }
    }
}
