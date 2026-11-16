<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Catálogo público
    |--------------------------------------------------------------------------
    |
    | only_verified = false: los listados públicos muestran los productores con
    |                        Estado "Activo" (aunque estén Pendientes de verificación;
    |                        la app ve `estado_verificacion` y puede mostrar la insignia).
    | only_verified = true : solo productores Verificados (y sus productos).
    |
    */

    'only_verified' => (bool) env('CATALOG_ONLY_VERIFIED', false),

    'per_page_default' => 15,
    'per_page_max' => 100,

];
