<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Única fuente de verdad para URLs de imágenes.
 *
 * En BD se guarda SOLO la ruta relativa dentro del disco (p. ej. "avatars/x.png").
 * Al responder, la URL absoluta sale de Storage::disk(media_disk)->url(), que a
 * su vez se arma con APP_URL / la URL del disco. Nunca hay hosts fijos en código.
 */
class MediaUrl
{
    /** Disco configurado por .env (MEDIA_DISK). */
    public static function disk(): string
    {
        return (string) config('filesystems.media_disk', 'public');
    }

    /**
     * Valor guardado en BD → URL absoluta (o null).
     *
     * - null / vacío           → null
     * - assets/... (Flutter)   → null: son assets empaquetados en la app, el servidor no los sirve.
     * - https://externo/...    → se respeta tal cual (p. ej. avatar de Google).
     * - ruta relativa o URL local antigua (/storage/..., http://localhost:8001/storage/...)
     *                          → URL absoluta con el disco configurado.
     */
    public static function resolve(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (self::esAssetDeLaApp($value)) {
            return null;
        }

        $normalizado = self::paraAlmacenar($value);

        if (preg_match('#^https?://#i', $normalizado)) {
            return $normalizado; // externa
        }

        return Storage::disk(self::disk())->url(ltrim($normalizado, '/'));
    }

    /**
     * Normaliza lo que va a la BD: ruta relativa al disco.
     * Las URLs externas (otro host no local) se dejan intactas.
     */
    public static function paraAlmacenar(string $value): string
    {
        $value = trim($value);

        if (preg_match('#^https?://#i', $value)) {
            $partes = parse_url($value);
            $host = $partes['host'] ?? '';
            $path = $partes['path'] ?? '';

            if (self::esHostLocal($host) && str_starts_with($path, '/storage/')) {
                return ltrim(substr($path, strlen('/storage/')), '/');
            }

            return $value;
        }

        if (str_starts_with($value, '/storage/')) {
            return ltrim(substr($value, strlen('/storage/')), '/');
        }

        if (str_starts_with($value, 'storage/')) {
            return ltrim(substr($value, strlen('storage/')), '/');
        }

        return $value;
    }

    /**
     * @param  array<int, string|null>|null  $values
     * @return list<string>
     */
    public static function resolveMany(?array $values): array
    {
        return collect($values ?? [])
            ->map(fn ($v) => is_string($v) ? self::resolve($v) : null)
            ->filter()
            ->values()
            ->all();
    }

    public static function esAssetDeLaApp(string $value): bool
    {
        return str_starts_with($value, 'assets/') || str_starts_with($value, '/assets/');
    }

    /** ¿El host es de esta misma instalación/desarrollo? (APP_URL, localhost, IPs privadas). */
    public static function esHostLocal(string $host): bool
    {
        $host = strtolower($host);

        if ($host === '') {
            return false;
        }

        if ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.test')) {
            return true;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if ($appHost && strtolower($appHost) === $host) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // Privadas, reservadas y loopback (127.0.0.1, 10.x, 192.168.x, ...)
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
        }

        return false;
    }
}
