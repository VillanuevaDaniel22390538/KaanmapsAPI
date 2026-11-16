# Ka'an Maps API (v1)

Backend Laravel para el marketplace de productores de Quintana Roo consumido por la app móvil Flutter (Clean Architecture + MVVM).

Ver el detalle completo de contratos JSON (ANTES / DESPUÉS) y campos deprecados en [CHANGELOG-API.md](./CHANGELOG-API.md).

---

## Variables de entorno (`.env`)

Revisa `.env.example` para ver todas las variables disponibles:

```dotenv
APP_URL=http://localhost:8000
APP_DEBUG=false                  # true solo en local; false en producción

MEDIA_DISK=public                # disco de archivos para MediaUrl (APP_URL + disco)
CORS_ALLOWED_ORIGINS=*           # en producción: lista separada por comas
CATALOG_ONLY_VERIFIED=false      # true para listar solo productores Verificados

API_RATE_PUBLIC=60               # GET públicos (req/min por IP)
API_RATE_LOGIN=5                 # POST /v1/login (req/min por email+IP)
API_RATE_WRITES=30               # rutas autenticadas (req/min por usuario)
```

---

## Flujo de despliegue / actualización en BD local

### 1. Respaldar la base de datos antes de migrar

```bash
mkdir -p ~/backups
/Applications/XAMPP/xamppfiles/bin/mysqldump -u root -p \
  --single-transaction --routines --triggers --default-character-set=utf8mb4 \
  kaanmapsdb > ~/backups/kaanmapsdb_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Previsualizar y ejecutar migraciones nuevas (reversibles)

```bash
php artisan migrate --pretend
php artisan migrate
```

### 3. Ejecutar seeders idempotentes (en este orden)

```bash
php artisan db:seed --class=CategoriaSeeder
php artisan db:seed --class=ReasignarCategoriasProductosSeeder
php artisan db:seed --class=ProductosDemoSeeder   # Solo local/staging (no correr en producción)
```

### 4. Enlazar almacenamiento público

```bash
php artisan storage:link
```

### 5. Correr tests

Los tests corren exclusivamente sobre SQLite en memoria (`phpunit.xml`) y nunca tocan la base de datos MySQL local:

```bash
php artisan test
```
