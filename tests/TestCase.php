<?php

namespace Slides\Saml2\Tests;

use Illuminate\Contracts\Config\Repository;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use WithWorkbench;

    /**
     * Define test environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function defineEnvironment($app)
    {
        tap($app['config'], static function (Repository $config) {
            $config->set('database.default', 'testing');
            $config->set('session.driver', 'array');
            $config->set('saml2.useRoutes', true);
            $config->set('saml2.routesMiddleware', []);
        });
    }
}
