<?php

/**
 * Árbol de categorías: Categoría → Subcategoría → Tipo.
 *
 * ORDEN IMPORTA: el orden de las subcategorías (nivel 2, recorriendo las
 * categorías en el orden de abajo) coincide con los ids locales 1–18 de la app
 * Flutter (Ganadería 1–4, Artesanías 5–10, Apicultura 11–14, Agricultura 15–18).
 * ReasignarCategoriasProductosSeeder usa esa correspondencia.
 *
 * Para añadir tipos pendientes (Joyería, Apicultura, Agricultura…) basta con
 * llenar el arreglo 'tipos' y volver a correr CategoriaSeeder (es idempotente).
 *
 * 'descripcion' y 'color' solo se usan al CREAR; si la fila ya existe se respeta.
 */
return [
    [
        'nombre' => 'Ganadería',
        'subcategorias' => [
            ['nombre' => 'Aves de Corral', 'tipos' => ['Pollos y Gallinas', 'Patos', 'Pavos']],
            ['nombre' => 'Porcinos', 'tipos' => ['Cerdo Pelón Mexicano', 'Lechones', 'Cerdos de Engorda']],
            ['nombre' => 'Bovinos', 'tipos' => ['Ganado de Engorda', 'Ganado Lechero', 'Becerros']],
            ['nombre' => 'Acuicolas', 'tipos' => ['Pescado de cultivo', 'Camarón y Mariscos']],
        ],
    ],
    [
        'nombre' => 'Artesanías',
        'subcategorias' => [
            ['nombre' => 'Textiles y Bordados', 'tipos' => ['Guayaberas', 'Huipiles y Ternos', 'Camisas y Blusas Bordadas', 'Textiles para el Hogar']],
            ['nombre' => 'Talla en Madera', 'tipos' => ['Figuras y Máscaras', 'Utensilios de Cocina y Hogar']],
            ['nombre' => 'Hamaquería', 'tipos' => ['Hamacas de Nylon', 'Hamacas de Algodón', 'Sillas Hamaca']],
            ['nombre' => 'Cestería', 'tipos' => ['Canastos y Cestas', 'Sombreros de Palma', 'Bolsos de Bejuco']],
            ['nombre' => 'Alfarería', 'tipos' => ['Macetas', 'Vajillas y Platos', 'Decoración']],
            ['nombre' => 'Joyería Artesanal', 'tipos' => []], // tipos pendientes
        ],
    ],
    [
        'nombre' => 'Apicultura',
        'subcategorias' => [
            ['nombre' => 'Miel de Abeja', 'tipos' => []],
            ['nombre' => 'Meliponicultura', 'tipos' => []],
            ['nombre' => 'Derivados Apícolas', 'tipos' => []],
            ['nombre' => 'Cosmética Natural', 'tipos' => []],
        ],
    ],
    [
        'nombre' => 'Agricultura',
        'subcategorias' => [
            ['nombre' => 'Frutas', 'tipos' => []],
            ['nombre' => 'Verduras', 'tipos' => []],
            ['nombre' => 'Chiles', 'tipos' => []],
            ['nombre' => 'Semillas e Injertos', 'tipos' => []],
        ],
    ],
];
