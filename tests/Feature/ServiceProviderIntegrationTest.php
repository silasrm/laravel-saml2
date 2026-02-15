<?php

declare(strict_types=1);

namespace Slides\Saml2\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Slides\Saml2\Http\Middleware\ResolveTenant;
use Slides\Saml2\Tests\TestCase;

/**
 * @internal
 */
class ServiceProviderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function testRegistersPackageRoutes(): void
    {
        self::assertTrue(Route::has('saml.login'));
        self::assertTrue(Route::has('saml.logout'));
        self::assertTrue(Route::has('saml.metadata'));
        self::assertTrue(Route::has('saml.acs'));
        self::assertTrue(Route::has('saml.sls'));

        $loginRoute = Route::getRoutes()->getByName('saml.login');
        self::assertNotNull($loginRoute);
        self::assertSame('saml2/{uuid}/login', ltrim($loginRoute->uri(), '/'));

        $loginRouteMiddleware = (array) $loginRoute->getAction('middleware');
        self::assertContains('saml2.resolveTenant', $loginRouteMiddleware);
    }

    public function testLoadsPackageMigrations(): void
    {
        self::assertTrue(Schema::hasTable('saml2_tenants'));
        self::assertTrue(Schema::hasColumn('saml2_tenants', 'uuid'));
        self::assertTrue(Schema::hasColumn('saml2_tenants', 'relay_state_url'));
        self::assertTrue(Schema::hasColumn('saml2_tenants', 'name_id_format'));
    }

    public function testRegistersResolveTenantMiddlewareAlias(): void
    {
        $middleware = $this->app['router']->getMiddleware();

        self::assertArrayHasKey('saml2.resolveTenant', $middleware);
        self::assertSame(ResolveTenant::class, $middleware['saml2.resolveTenant']);
    }
}
