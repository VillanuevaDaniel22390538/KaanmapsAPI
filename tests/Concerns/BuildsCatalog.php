<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Helpers para armar datos del esquema legacy (columnas Pascal_Snake)
 * sin depender de $fillable de los modelos.
 */
trait BuildsCatalog
{
    protected int $seq = 0;

    protected function crearUsuario(array $attrs = []): User
    {
        $n = ++$this->seq;
        $id = DB::table('usuarios')->insertGetId(array_merge([
            'Nombre_Usuario' => "usuario{$n}",
            'Email' => "usuario{$n}@example.com",
            'Contraseña_Hash' => Hash::make('secret123'),
            'Tipo_Usuario' => 'Productor',
            'Telefono' => '9981234567',
            'Avatar_URL' => null,
            'Estado' => 'Activo',
        ], $attrs));

        return User::findOrFail($id);
    }

    protected function crearProductor(array $attrs = []): int
    {
        $n = ++$this->seq;

        return DB::table('productores')->insertGetId(array_merge([
            'Nombre_Negocio' => "Negocio {$n}",
            'Descripcion_Negocio' => "Descripcion del negocio {$n}",
            'Tipo_Actividad' => 'Agricultura',
            'Logo_URL' => null,
            'Slogan' => null,
            'Fecha_Registro' => '2026-01-15',
            'Estado_Verificacion' => 'Pendiente',
            'Estado' => 'Activo',
        ], $attrs), 'ID_Productor');
    }

    protected function crearProducto(int $productorId, int $categoriaId, array $attrs = []): int
    {
        $n = ++$this->seq;

        return DB::table('productos')->insertGetId(array_merge([
            'ID_Productor' => $productorId,
            'ID_Categoria' => $categoriaId,
            'Nombre_Producto' => "Producto {$n}",
            'Descripcion' => "Descripcion {$n}",
            'Precio' => '100.00',
            'Moneda' => 'MXN',
            'Tipo_Unidad' => 'kg',
            'Cantidad_Inventario' => 10,
            'Disponible' => 1,
            'URLs_Imagenes' => json_encode([]),
            'Etiquetas' => json_encode([]),
            'Fecha_Registro' => '2026-01-15',
        ], $attrs), 'ID_Producto');
    }

    protected function crearUbicacion(int $productorId, array $attrs = []): int
    {
        return DB::table('ubicaciones_productores')->insertGetId(array_merge([
            'ID_Productor' => $productorId,
            'Latitud' => '18.525200',
            'Longitud' => '-88.302300',
            'Direccion' => 'Calle 1',
            'Localidad' => 'Chetumal',
            'Municipio' => 'Othon P. Blanco',
            'Estado' => 'Quintana Roo',
            'Pais' => 'México',
            'Es_Principal' => 1,
        ], $attrs), 'ID_Ubicacion');
    }
}
