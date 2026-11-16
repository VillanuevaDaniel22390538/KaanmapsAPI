# CHANGELOG — Ka'an Maps API v1

Este documento detalla los cambios realizados en la API `/v1` para ajustarla a la arquitectura Flutter (Clean Architecture + MVVM) y dejarla lista para producción.

> Nota: El archivo `CHANGELOG.md` original del repositorio corresponde a las notas de versión del framework Laravel; este `CHANGELOG-API.md` documenta los cambios de la API de Ka'an Maps.

---

## Resumen de cambios (Secciones A–G)

- **A. Nombres y tipos consistentes (`snake_case` minúscula):**
  - Todos los API Resources (`CategoriaResource`, `UbicacionResource`, `ProductoResource`, `ProductorResource`) devuelven claves exclusivamente en `snake_case` minúscula, sin renombrar las columnas de la BD.
  - `ubicaciones[].latitud` y `ubicaciones[].longitud` ahora salen como `float` (antes `string`).
  - `ubicaciones[].es_principal` sale como `bool` (antes `0`/`1`).
  - `productos[].precio` se mantiene como `string` decimal con 2 decimales (`"3000.00"`) y `moneda` en código ISO 4217 mayúscula (`"MXN"`).
- **B. URLs de imágenes consistentes:**
  - Todas las imágenes subidas al servidor (`logo`, `usuario.avatar`, `imagenes`, `imagenes_instalaciones`) se almacenan en BD como rutas relativas al disco (`logos/...`, `avatars/...`, `productos/...`, `instalaciones/...`) y se serializan como URL absoluta usando `Storage::disk(config('filesystems.media_disk'))->url(...)` (configurado por `APP_URL` y `MEDIA_DISK` en `.env`, sin hosts fijos).
  - Las URLs externas (ej. avatares de Google `https://lh3.googleusercontent.com/...`) se conservan sin cambios.
  - Las rutas heredadas de assets empaquetados dentro de Flutter (`assets/images/app/...`) devuelven `null` (en listas como `imagenes`, se omiten para evitar URLs rotas `404`), permitiendo que la app use su placeholder local por categoría.
  - La migración de datos `2026_11_16_000000_normalize_media_urls` normaliza las URLs locales absolutas o con `/storage/` en BD y guarda respaldo reversible en `media_url_backups`.
- **C. Categorías en 3 niveles (`Categoría → Subcategoría → Tipo`):**
  - Nuevas columnas en `categorias_productos`: `slug` (único), `orden` (`int`), `activo` (`bool`).
  - `GET /v1/categorias` devuelve únicamente categorías activas en lista plana con `nivel` (1–3) calculado por profundidad.
  - Nuevos filtros: `?nivel=1|2|3` y `?padre_id=<id>`.
  - Encabezados HTTP: `Cache-Control: public, max-age=3600` y `ETag` (con soporte `304 Not Modified`).
  - Seeder idempotente `CategoriaSeeder`: reutiliza por `Nombre_Categoria` (`Apicultura`, `Agricultura`, `Ganadería`, `Artesanías`, `Joyería Artesanal`), crea `Acuicolas` bajo `Ganadería`, y marca `activo=false` (sin borrar) las categorías fuera del árbol (`Pesca`, `Acuacultura`, `Otro`, etc.).
- **D. Productos y filtros server-side:**
  - Corregido el error `500` en `GET /v1/productos` (doble `json_decode` sobre un campo ya casteado a `array`) y el fallo en `GET /v1/productos/{id}` (relación `Producto::productor()` faltante).
  - `categoria_id` apunta al nodo más específico asignado al producto, y se añaden ya calculados por ancestros: `categoria_raiz_id`, `subcategoria_id` y `tipo_id` (`null` si no aplica).
  - Filtros en `GET /v1/productos`: `categoria_id` (incluye descendientes), `productor_id`, `disponible` (`true`/`false`/`1`/`0`), `etiqueta`, `q` (nombre y descripción), `sort` (`recientes`, `precio_asc`, `precio_desc`), `per_page` (default `15`, máximo `100`).
  - Seeders idempotentes `ReasignarCategoriasProductosSeeder` y `ProductosDemoSeeder` (con etiqueta `"demo"`).
- **E y F. Productores (nuevos campos UI, privacidad y filtros):**
  - Nuevos campos en `ProductorResource`: `categoria_principal_id`, `telefono_contacto` (público del negocio), `email`, `facebook`, `instagram`, `horario`, `imagenes_instalaciones[]` (desde la nueva tabla `productor_imagenes`), `fecha_registro`.
  - Privacidad (F1): `usuario.nombre` (username) y `usuario.telefono` (dato personal de la cuenta) **se eliminaron** del endpoint público; `usuario` solo expone `{ "avatar": ... }`.
  - Visibilidad (F2): por defecto se muestran los productores con `Estado = "Activo"` (incluyendo `Pendiente`), configurable mediante `CATALOG_ONLY_VERIFIED=true` en `.env`.
  - `productos` embebidos se eliminan de `GET /v1/productores` (listado) para reducir peso; en `GET /v1/productores/{id}` se incluyen únicamente bajo `?include=productos` formateados con `ProductoResource`.
  - Filtros en `GET /v1/productores`: `q` (alias legacy `search`), `categoria_id` (incluye descendientes; coincide si `categoria_principal_id` está en el subárbol o si tiene productos en él), `municipio`, `tipo_actividad` (deprecado), `estado_verificacion`, `per_page` (máximo `100`).
- **G. Calidad para producción:**
  - Policies (`ProductorPolicy`, `ProductoPolicy`) y validación de categorías activas asignables (`CategoriaAsignable`).
  - Eager loading (`usuario`, `ubicaciones`, `imagenes`) y árbol de categorías cacheado (`CategoriaTree`) para evitar N+1.
  - Índices nuevos en `categorias_productos`, `productores`, `productor_imagenes` y `productos`.
  - Rate limiting configurable por `.env` (`public-api`, `login`, `writes`), CORS por `.env` (`CORS_ALLOWED_ORIGINS`), y `Cache-Control: no-cache` + `ETag` en `/productos` y `/productores`.

---

## JSON ANTES y DESPUÉS por endpoint

### 1. `GET /v1/categorias`

#### ANTES
```json
{
  "data": [
    {
      "ID_Categoria": 1,
      "Nombre_Categoria": "Apicultura",
      "ID_Categoria_Padre": null,
      "Descripcion": "Productos derivados de la miel y las abejas",
      "URL_Icono": null,
      "Color_Hex": "#FFC107"
    },
    {
      "ID_Categoria": 28,
      "Nombre_Categoria": "Otro",
      "ID_Categoria_Padre": null,
      "Descripcion": null,
      "URL_Icono": null,
      "Color_Hex": null
    }
  ]
}
```

#### DESPUÉS (`GET /v1/categorias`, admite `?nivel=1|2|3` y `?padre_id=<id>`)
Solo devuelve categorías activas (`activo = true`), ordenadas por `orden` y `Nombre_Categoria`. Incluye encabezados `Cache-Control: public, max-age=3600` y `ETag`.

```json
{
  "data": [
    {
      "id": 3,
      "nombre": "Ganadería",
      "slug": "ganaderia",
      "padre_id": null,
      "nivel": 1,
      "descripcion": "Carne, leche y derivados",
      "color_hex": "#8D6E63",
      "icono_url": null,
      "orden": 1
    },
    {
      "id": 29,
      "nombre": "Aves de Corral",
      "slug": "aves-de-corral",
      "padre_id": 3,
      "nivel": 2,
      "descripcion": null,
      "color_hex": "#8D6E63",
      "icono_url": null,
      "orden": 1
    },
    {
      "id": 30,
      "nombre": "Pollos y Gallinas",
      "slug": "pollos-y-gallinas",
      "padre_id": 29,
      "nivel": 3,
      "descripcion": null,
      "color_hex": "#8D6E63",
      "icono_url": null,
      "orden": 1
    }
  ]
}
```

---

### 2. `GET /v1/productos` y `GET /v1/productos/{id}`

#### ANTES
> En el código base anterior, `GET /v1/productos` devolvía **HTTP 500** (`json_decode(): Argument #1 must be of type string, array given`) y `GET /v1/productos/{id}` fallaba por la relación `productor` inexistente en el modelo. Cuando un producto se serializaba dentro de `/productores`, salía con la estructura cruda de BD:

```json
{
  "ID_Producto": 76,
  "ID_Productor": 1,
  "ID_Categoria": 3,
  "Nombre_Producto": "Pollo entero de granja",
  "Descripcion": "Pollo criado en granja con alimentacion controlada, listo para venta al consumidor.",
  "Precio": "95.00",
  "Moneda": "MXN",
  "Tipo_Unidad": "kg",
  "Cantidad_Inventario": 60,
  "Disponible": true,
  "URLs_Imagenes": [
    "assets/images/app/Products/GenericImages/P_ganaderia.png"
  ],
  "Etiquetas": null,
  "Fecha_Registro": "2026-11-15"
}
```

#### DESPUÉS (`GET /v1/productos` — paginado `{data, links, meta}`)
Filtros soportados: `categoria_id` (incluye descendientes), `productor_id`, `disponible`, `etiqueta`, `q`, `sort` (`recientes`, `precio_asc`, `precio_desc`), `per_page` (default `15`, máx `100`).

```json
{
  "data": [
    {
      "id": 76,
      "productor_id": 1,
      "categoria_id": 29,
      "categoria_raiz_id": 3,
      "subcategoria_id": 29,
      "tipo_id": null,
      "nombre": "Pollo entero de granja",
      "descripcion": "Pollo criado en granja con alimentacion controlada, listo para venta al consumidor.",
      "precio": "95.00",
      "moneda": "MXN",
      "tipo_unidad": "kg",
      "inventario": 60,
      "disponible": true,
      "imagenes": [
        "https://api.tu-dominio.com/storage/productos/ejemplo.jpg"
      ],
      "etiquetas": ["fresco"],
      "fecha_registro": "2026-11-15"
    }
  ],
  "links": {
    "first": "https://api.tu-dominio.com/v1/productos?page=1",
    "last": "https://api.tu-dominio.com/v1/productos?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "links": [],
    "path": "https://api.tu-dominio.com/v1/productos",
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

> Nota: Para `GET /v1/productos/{id}`, el objeto tiene el mismo formato dentro de `{"data": { ... }}`. Si un producto tenía `assets/images/app/...`, el arreglo `imagenes` sale vacío `[]` (la app debe mostrar su placeholder por categoría).

---

### 3. `GET /v1/productores` y `GET /v1/productores/{id}`

#### ANTES
```json
{
  "data": [
    {
      "id": 1,
      "nombre_negocio": "Granja Pecuaria FERCO y Vidal",
      "descripcion": "Granja avicola familiar...",
      "tipo_actividad": "Ganadería",
      "slogan": "Aves de calidad, tradicion del sureste",
      "logo": "assets/images/app/Business/GenericImages/B_ganaderia.png",
      "estado_verificacion": "Pendiente",
      "usuario": {
        "nombre": "juanperez",
        "telefono": "9981909823",
        "avatar": "http://localhost:8001/storage/avatars/abc.jpg"
      },
      "ubicaciones": [
        {
          "ID_Ubicacion": 47,
          "ID_Productor": 1,
          "Latitud": "18.525200",
          "Longitud": "-88.302300",
          "Direccion": "Av. Erick Paolo Martinez...",
          "Localidad": "Chetumal",
          "Municipio": "Othon P. Blanco",
          "Estado": "Quintana Roo",
          "Pais": "México",
          "Precision_Geolocalizacion": null,
          "Es_Principal": 1
        }
      ],
      "productos": [
        {
          "ID_Producto": 76,
          "Nombre_Producto": "Pollo entero de granja",
          "Precio": "95.00"
        }
      ]
    }
  ],
  "links": { "...": "..." },
  "meta": { "...": "..." }
}
```

#### DESPUÉS (`GET /v1/productores`)
Filtros soportados: `q` (alias legacy `search`), `categoria_id` (incluye descendientes; coincide por categoría principal o por productos), `municipio`, `estado_verificacion`, `tipo_actividad` (deprecado), `per_page` (default `15`, máx `100`).

```json
{
  "data": [
    {
      "id": 1,
      "nombre_negocio": "Granja Pecuaria FERCO y Vidal",
      "descripcion": "Granja avicola familiar...",
      "tipo_actividad": "Ganadería",
      "categoria_principal_id": 3,
      "slogan": "Aves de calidad, tradicion del sureste",
      "logo": null,
      "estado_verificacion": "Pendiente",
      "telefono_contacto": "9831112233",
      "email": "contacto@granjaferco.mx",
      "facebook": "https://facebook.com/granjaferco",
      "instagram": "@granjaferco",
      "horario": "Lun-Vie 9:00-18:00",
      "imagenes_instalaciones": [
        "https://api.tu-dominio.com/storage/instalaciones/foto1.jpg"
      ],
      "fecha_registro": "2026-11-15",
      "usuario": {
        "avatar": "https://api.tu-dominio.com/storage/avatars/abc.jpg"
      },
      "ubicaciones": [
        {
          "id": 47,
          "productor_id": 1,
          "latitud": 18.5252,
          "longitud": -88.3023,
          "direccion": "Av. Erick Paolo Martinez...",
          "localidad": "Chetumal",
          "municipio": "Othon P. Blanco",
          "estado": "Quintana Roo",
          "pais": "México",
          "precision_geolocalizacion": null,
          "es_principal": true
        }
      ]
    }
  ],
  "links": { "...": "..." },
  "meta": { "...": "..." }
}
```

> Nota sobre `GET /v1/productores/{id}`: devuelve exactamente esa misma estructura dentro de `{"data": { ... }}` sin la clave `productos`. Si se solicita `GET /v1/productores/{id}?include=productos`, se agrega `"productos": [ ... ]` formateado con `ProductoResource`.

---

### 4. `GET /v1/ubicaciones` y `POST /v1/ubicaciones` (Requiere Token)

#### ANTES
```json
{
  "data": [
    {
      "ID_Ubicacion": 47,
      "ID_Productor": 1,
      "Latitud": "18.525200",
      "Longitud": "-88.302300",
      "Direccion": "Av. Erick Paolo Martinez...",
      "Localidad": "Chetumal",
      "Municipio": "Othon P. Blanco",
      "Estado": "Quintana Roo",
      "Pais": "México",
      "Precision_Geolocalizacion": null,
      "Es_Principal": 1
    }
  ]
}
```

#### DESPUÉS (`GET /v1/ubicaciones?productor_id=1`)
```json
{
  "data": [
    {
      "id": 47,
      "productor_id": 1,
      "latitud": 18.5252,
      "longitud": -88.3023,
      "direccion": "Av. Erick Paolo Martinez...",
      "localidad": "Chetumal",
      "municipio": "Othon P. Blanco",
      "estado": "Quintana Roo",
      "pais": "México",
      "precision_geolocalizacion": null,
      "es_principal": true
    }
  ]
}
```

`POST /v1/ubicaciones` responde HTTP `201` con:
```json
{
  "data": {
    "id": 92,
    "productor_id": 1,
    "latitud": 18.5,
    "longitud": -88.3,
    "direccion": null,
    "localidad": null,
    "municipio": "Bacalar",
    "estado": "Quintana Roo",
    "pais": "México",
    "precision_geolocalizacion": null,
    "es_principal": false
  },
  "success": true,
  "message": "Ubicación registrada correctamente."
}
```

---

### 5. Endpoints de escritura existentes (compatibilidad)

| Endpoint | Entrada aceptada | Respuesta |
|---|---|---|
| `POST /v1/login` | `email`, `password`, `device_name` | Sin cambios (`rate limit` 5/min por email+IP) |
| `POST /v1/logout` | Bearer token | Sin cambios |
| `POST /v1/productores`, `PUT /v1/productores/{id}` | Campos `Pascal_Snake` originales **y** alias `snake_case` (`nombre_negocio`, `categoria_principal_id`, `email`, `telefono_contacto`, `horario`, `imagenes_instalaciones[]`...) | `{ "data": <ProductorResource>, "success": true, "message": "..." }` |
| `DELETE /v1/productores/{id}` | — (requiere dueño o `SuperAdmin`/`Admin_Gobierno`) | `{ "success": true, "message": "Productor eliminado correctamente." }` |
| `POST /v1/productos`, `PUT /v1/productos/{id}` | Campos `Pascal_Snake` originales **y** alias `snake_case` (`productor_id`, `categoria_id`, `nombre`, `precio`, `inventario`, `disponible`, `imagenes[]`, `etiquetas[]`) | `{ "data": <ProductoResource>, "success": true, "message": "..." }` |
| `DELETE /v1/productos/{id}` | — (requiere dueño o personal autorizado) | `{ "success": true, "message": "Producto eliminado correctamente." }` |
| `GET /v1/ubicaciones`, `POST /v1/ubicaciones` | Campos `Pascal_Snake` originales **y** alias `snake_case` (`productor_id`, `latitud`, `longitud`, `es_principal`...) | `UbicacionResource` (`snake_case`, `latitud`/`longitud` float) |

> **Regla de categoría en productos:** al crear o actualizar un producto, `ID_Categoria` / `categoria_id` debe pertenecer a una categoría **activa** de **nivel 2 o 3** (subcategoría o tipo, no raíz).
> **Auto-verificación bloqueada:** un productor que actualiza su propio perfil no puede cambiar `Estado_Verificacion` ni `Estado` (solo el personal autorizado puede modificarlos).

---

## Campos y parámetros deprecados (guía para adaptar Flutter)

| Elemento | Estado | Reemplazo recomendado |
|---|---|---|
| `productores[].tipo_actividad` | **Deprecado** (solo lectura, derivado de la categoría si falta) | Usar `categoria_principal_id` |
| `GET /v1/productores?search=` | **Deprecado** (alias compatible) | Usar `?q=` |
| `productores.usuario.nombre`, `productores.usuario.telefono` | **Eliminados** del endpoint público | Usar `nombre_negocio` y `telefono_contacto` |
| `productores` embebiendo `productos` en el listado | **Eliminado** del listado | Consultar `GET /v1/productos?productor_id={id}` o `GET /v1/productores/{id}?include=productos` |
| Columnas legacy `productos.subcategoria_id`, `productos.tipos_id` | **Ignoradas** en respuestas | Usar `categoria_id`, `categoria_raiz_id`, `subcategoria_id`, `tipo_id` calculados en `ProductoResource` |
| Columnas legacy `productores.hora_apertura`, `productores.hora_cierre`, `productores.imagenes_instalaciones` | **Deprecadas** | Usar `horario` e `imagenes_instalaciones` (desde `productor_imagenes`) |
