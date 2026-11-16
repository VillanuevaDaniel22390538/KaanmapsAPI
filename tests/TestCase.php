<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Salvaguarda: los tests ejecutan migrate:fresh. NUNCA contra la BD real.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException(
                'Los tests solo pueden correr contra sqlite (:memory:). Revisa phpunit.xml / variables de entorno.'
            );
        }
    }
}
