<?php

namespace Slides\Saml2\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Slides\Saml2\Tests\TestCase;

class ServiceProviderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function testRegistersPackageRoutes()
    {
        $this->assertTrue(Route::has('saml.login'));
        $this->assertTrue(Route::has('saml.logout'));
        $this->assertTrue(Route::has('saml.metadata'));
        $this->assertTrue(Route::has('saml.acs'));
        $this->assertTrue(Route::has('saml.sls'));

        $loginRoute = Route::getRoutes()->getByName('saml.login');
        $this->assertNotNull($loginRoute);
        $this->assertSame('saml2/{uuid}/login', ltrim($loginRoute->uri(), '/'));
    }

    public function testLoadsPackageMigrations()
    {
        $this->assertTrue(Schema::hasTable('saml2_tenants'));
        $this->assertTrue(Schema::hasColumn('saml2_tenants', 'uuid'));
        $this->assertTrue(Schema::hasColumn('saml2_tenants', 'relay_state_url'));
        $this->assertTrue(Schema::hasColumn('saml2_tenants', 'name_id_format'));
    }
}
