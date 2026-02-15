<?php

declare(strict_types=1);

namespace Slides\Saml2\Tests\Fakes;

use Illuminate\Contracts\Container\Container;
use Slides\Saml2\Auth as SamlAuth;
use Slides\Saml2\Models\Tenant;
use Slides\Saml2\OneLoginBuilder;

class FakeOneLoginBuilder extends OneLoginBuilder
{
    /** @var Tenant|null */
    protected $tenant;

    public function __construct(Container $app)
    {
        parent::__construct($app);
    }

    /**
     * Set a tenant.
     *
     * @return $this
     */
    public function withTenant(Tenant $tenant)
    {
        $this->tenant = $tenant;

        return $this;
    }

    /**
     * Bind mocked auth instance for feature tests.
     *
     * @return void
     */
    public function bootstrap()
    {
        if (!$this->app->bound('saml2.test.auth')) {
            throw new \RuntimeException('Missing [saml2.test.auth] binding for FakeOneLoginBuilder.');
        }

        /** @var SamlAuth $auth */
        $auth = $this->app->make('saml2.test.auth');

        $this->app->instance(SamlAuth::class, $auth);
        $this->app->instance('Slides\Saml2\Auth', $auth);
    }
}
