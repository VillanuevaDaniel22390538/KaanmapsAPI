<?php

namespace App\Providers;

use App\Repositories\Eloquent\ProductoRepository;
use App\Repositories\Eloquent\ProductorRepository;
use App\Repositories\Interfaces\ProductoRepositoryInterface;
use App\Repositories\Interfaces\ProductorRepositoryInterface;
use App\Support\CategoriaTree;
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
        //
    }
}
