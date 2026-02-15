<?php declare(strict_types=1);

namespace Slides\Saml2\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Slides\Saml2\Http\Middleware\ResolveTenant;
use Slides\Saml2\Tests\TestCase;

class ServiceProviderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function testRegistersPackageRoutes(): void
    {
        $this->assertTrue(Route::has('saml.login'));
        $this->assertTrue(Route::has('saml.logout'));
        $this->assertTrue(Route::has('saml.metadata'));
        $this->assertTrue(Route::has('saml.acs'));
        $this->assertTrue(Route::has('saml.sls'));

        $loginRoute = Route::getRoutes()->getByName('saml.login');
        $this->assertNotNull($loginRoute);
        $this->assertSame('saml2/{uuid}/login', ltrim($loginRoute->uri(), '/'));

        $loginRouteMiddleware = (array) $loginRoute->getAction('middleware');
        $this->assertContains('saml2.resolveTenant', $loginRouteMiddleware);
    }

    public function testLoadsPackageMigrations(): void
    {
        $this->assertTrue(Schema::hasTable('saml2_tenants'));
        $this->assertTrue(Schema::hasColumn('saml2_tenants', 'uuid'));
        $this->assertTrue(Schema::hasColumn('saml2_tenants', 'relay_state_url'));
        $this->assertTrue(Schema::hasColumn('saml2_tenants', 'name_id_format'));
    }

    public function testRegistersResolveTenantMiddlewareAlias(): void
    {
        $middleware = $this->app['router']->getMiddleware();

        $this->assertArrayHasKey('saml2.resolveTenant', $middleware);
        $this->assertSame(ResolveTenant::class, $middleware['saml2.resolveTenant']);
    }
}
