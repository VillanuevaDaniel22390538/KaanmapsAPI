<?php

namespace App\Providers;

use App\Repositories\Eloquent\ProductoRepository;
use App\Repositories\Eloquent\ProductorRepository;
use App\Repositories\Interfaces\ProductoRepositoryInterface;
use App\Repositories\Interfaces\ProductorRepositoryInterface;
use App\Support\CategoriaTree;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Inyección de dependencias (Principio de Inversión de Dependencias - SOLID)
        $this->app->bind(ProductorRepositoryInterface::class, ProductorRepository::class);
        $this->app->bind(ProductoRepositoryInterface::class, ProductoRepository::class);

        // Árbol de categorías: una sola lectura (cacheada) por request.
        $this->app->singleton(CategoriaTree::class, fn () => CategoriaTree::build());
    }

    public function boot(): void
    {
        RateLimiter::for('public-api', function (Request $request) {
            return Limit::perMinute((int) config('catalog.rate_limits.public', 60))
                ->by((string) $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email')));

            return Limit::perMinute((int) config('catalog.rate_limits.login', 5))
                ->by($email.'|'.$request->ip());
        });

        RateLimiter::for('writes', function (Request $request) {
            return Limit::perMinute((int) config('catalog.rate_limits.writes', 30))
                ->by((string) ($request->user()?->ID_Usuario ?: $request->ip()));
        });
    }
}
