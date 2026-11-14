<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\ProductorRepositoryInterface;
use App\Repositories\Eloquent\ProductorRepository;
use App\Repositories\Interfaces\ProductoRepositoryInterface;
use App\Repositories\Eloquent\ProductoRepository;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Inyección de dependencias Principio de Inversión de Dependencias - SOLID)
        $this->app->bind(ProductorRepositoryInterface::class, ProductorRepository::class);
        $this->app->bind(ProductoRepositoryInterface::class, ProductoRepository::class);
    }

    public function boot(): void
    {
        //
    }
}