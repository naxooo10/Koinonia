<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->app->environment('testing')) {
            throw new RuntimeException(
                'Las pruebas requieren el entorno testing.'
            );
        }

        if (
            config('database.default') !== 'mongodb'
            || config('database.connections.mongodb.driver') !== 'mongodb'
            || config('database.connections.mongodb.database')
                !== 'Koinonia_testing'
            || config('database.connections.mongodb.dsn')
                !== 'mongodb://127.0.0.1:27017'
        ) {
            throw new RuntimeException(
                'Las pruebas requieren MongoDB local y la base Koinonia_testing. Ejecuta php artisan config:clear.'
            );
        }

        if (
            config('session.driver') !== 'array'
            || config('cache.default') !== 'array'
        ) {
            throw new RuntimeException(
                'Las pruebas requieren sesiones y caché en memoria.'
            );
        }
    }
}