<?php

declare(strict_types=1);

namespace Slides\Saml2\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Slides\Saml2\Auth;
use Slides\Saml2\Events\SignedIn;
use Slides\Saml2\Models\Tenant;
use Slides\Saml2\OneLoginBuilder;
use Slides\Saml2\Saml2User;
use Slides\Saml2\Tests\Fakes\FakeOneLoginBuilder;
use Slides\Saml2\Tests\TestCase;

/**
 * @internal
 */
class AuthFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(OneLoginBuilder::class, static function ($app) {
            return new FakeOneLoginBuilder($app);
        });
    }

    protected function tearDown(): void
    {
        \Mockery::close();

        parent::tearDown();
    }

    public function testMetadataRouteResolvesTenantFromDatabaseAndReturnsMetadata(): void
    {
        $tenant = Tenant::query()->create($this->tenantAttributes());

        $auth = \Mockery::mock(Auth::class);
        $auth->shouldReceive('getMetadata')->once()->andReturn('<xml>metadata</xml>');

        $this->app->instance('saml2.test.auth', $auth);

        $response = $this->get("/saml2/{$tenant->uuid}/metadata");

        $response->assertOk();
        self::assertSame(
            'text/xml; charset=utf-8',
            strtolower((string) $response->headers->get('Content-Type')),
        );
        $response->assertSee('<xml>metadata</xml>', false);
        $response->assertSessionHas('saml2.tenant.uuid', $tenant->uuid);
    }

    public function testMetadataRouteReturnsNotFoundWhenTenantDoesNotExist(): void
    {
        $response = $this->get('/saml2/non-existent-tenant/metadata');

        $response->assertNotFound();
    }

    public function testMetadataRouteReturnsNotFoundWhenTenantIsSoftDeleted(): void
    {
        $tenant = Tenant::query()->create($this->tenantAttributes());
        $tenant->delete();

        $response = $this->get("/saml2/{$tenant->uuid}/metadata");

        $response->assertNotFound();
    }

    public function testAcsRouteDispatchesSignedInEventAndRedirectsToIntendedUrl(): void
    {
        Event::fake([SignedIn::class]);

        $tenant = Tenant::query()->create($this->tenantAttributes());
        $samlUser = \Mockery::mock(Saml2User::class);
        $samlUser->shouldReceive('getIntendedUrl')->once()->andReturn('https://app.test/intended');

        $auth = \Mockery::mock(Auth::class);
        $auth->shouldReceive('acs')->once()->andReturn([]);
        $auth->shouldReceive('getSaml2User')->once()->andReturn($samlUser);

        $this->app->instance('saml2.test.auth', $auth);

        $response = $this->post("/saml2/{$tenant->uuid}/acs");

        $response->assertRedirect('https://app.test/intended');

        Event::assertDispatched(SignedIn::class, function (SignedIn $event) use ($auth, $samlUser) {
            return $event->getAuth() === $auth && $event->getSaml2User() === $samlUser;
        });
    }

    public function testAcsRouteRedirectsToTenantRelayStateWhenIntendedUrlIsMissing(): void
    {
        Event::fake([SignedIn::class]);

        $tenant = Tenant::query()->create($this->tenantAttributes([
            'relay_state_url' => '/tenant/home',
        ]));
        $samlUser = \Mockery::mock(Saml2User::class);
        $samlUser->shouldReceive('getIntendedUrl')->once()->andReturn(null);

        $auth = \Mockery::mock(Auth::class);
        $auth->shouldReceive('acs')->once()->andReturn([]);
        $auth->shouldReceive('getSaml2User')->once()->andReturn($samlUser);
        $auth->shouldReceive('getTenant')->once()->andReturn($tenant);

        $this->app->instance('saml2.test.auth', $auth);

        $response = $this->post("/saml2/{$tenant->uuid}/acs");

        $response->assertRedirect('/tenant/home');
    }

    public function testAcsRouteRedirectsToConfiguredLoginRouteWhenNoIntendedUrlOrRelayState(): void
    {
        Event::fake([SignedIn::class]);

        $tenant = Tenant::query()->create($this->tenantAttributes([
            'relay_state_url' => null,
        ]));
        config(['saml2.loginRoute' => '/configured/home']);

        $samlUser = \Mockery::mock(Saml2User::class);
        $samlUser->shouldReceive('getIntendedUrl')->once()->andReturn(null);

        $auth = \Mockery::mock(Auth::class);
        $auth->shouldReceive('acs')->once()->andReturn([]);
        $auth->shouldReceive('getSaml2User')->once()->andReturn($samlUser);
        $auth->shouldReceive('getTenant')->once()->andReturn($tenant);

        $this->app->instance('saml2.test.auth', $auth);

        $response = $this->post("/saml2/{$tenant->uuid}/acs");

        $response->assertRedirect('/configured/home');
    }

    public function testAcsRouteRedirectsToConfiguredErrorRouteAndFlashesErrors(): void
    {
        $tenant = Tenant::query()->create($this->tenantAttributes());

        config([
            'saml2.errorRoute' => '/sso/error',
            'saml2.loginRoute' => '/login',
        ]);

        $auth = \Mockery::mock(Auth::class);
        $auth->shouldReceive('acs')->once()->andReturn(['invalid_response']);
        $auth->shouldReceive('getLastErrorReason')->once()->andReturn('signature_validation_failed');
        $auth->shouldReceive('getTenant')->once()->andReturn($tenant);

        $this->app->instance('saml2.test.auth', $auth);

        $response = $this->post("/saml2/{$tenant->uuid}/acs");

        $response->assertRedirect('/sso/error');
        $response->assertSessionHas('saml2.error', ['invalid_response']);
        $response->assertSessionHas('saml2.error_detail', ['signature_validation_failed']);
    }

    public function testLoginRouteUsesTenantRelayStateWhenReturnToIsMissing(): void
    {
        $tenant = Tenant::query()->create($this->tenantAttributes([
            'relay_state_url' => '/tenant/home',
        ]));

        config(['saml2.loginRoute' => '/configured/home']);

        $auth = \Mockery::mock(Auth::class);
        $auth->shouldReceive('getTenant')->once()->andReturn($tenant);
        $auth->shouldReceive('login')->once()->with('/tenant/home');

        $this->app->instance('saml2.test.auth', $auth);

        $response = $this->get("/saml2/{$tenant->uuid}/login");

        $response->assertOk();
    }

    public function testLoginRouteUsesConfiguredLoginRouteWhenTenantRelayStateIsMissing(): void
    {
        $tenant = Tenant::query()->create($this->tenantAttributes([
            'relay_state_url' => null,
        ]));

        config(['saml2.loginRoute' => '/configured/home']);

        $auth = \Mockery::mock(Auth::class);
        $auth->shouldReceive('getTenant')->once()->andReturn($tenant);
        $auth->shouldReceive('login')->once()->with('/configured/home');

        $this->app->instance('saml2.test.auth', $auth);

        $response = $this->get("/saml2/{$tenant->uuid}/login");

        $response->assertOk();
    }

    public function testLoginRoutePrioritizesReturnToQueryParameter(): void
    {
        $tenant = Tenant::query()->create($this->tenantAttributes([
            'relay_state_url' => '/tenant/home',
        ]));
        config(['saml2.loginRoute' => '/configured/home']);

        $auth = \Mockery::mock(Auth::class);
        $auth->shouldReceive('getTenant')->once()->andReturn($tenant);
        $auth->shouldReceive('login')->once()->with('/explicit/return-to');

        $this->app->instance('saml2.test.auth', $auth);

        $response = $this->get("/saml2/{$tenant->uuid}/login?returnTo=/explicit/return-to");

        $response->assertOk();
    }

    public function testLogoutRouteForwardsQueryParametersToAuthLogout(): void
    {
        $tenant = Tenant::query()->create($this->tenantAttributes());

        $auth = \Mockery::mock(Auth::class);
        $auth->shouldReceive('logout')->once()->with(
            '/after-logout',
            'name-id-123',
            'session-index-456',
        );

        $this->app->instance('saml2.test.auth', $auth);

        $response = $this->get(
            "/saml2/{$tenant->uuid}/logout?returnTo=/after-logout&nameId=name-id-123&sessionIndex=session-index-456",
        );

        $response->assertOk();
    }

    public function testSlsRouteRedirectsToConfiguredLogoutRouteOnSuccess(): void
    {
        $tenant = Tenant::query()->create($this->tenantAttributes());

        config(['saml2.logoutRoute' => '/signed-out']);

        $auth = \Mockery::mock(Auth::class);
        $auth->shouldReceive('sls')->once()->with(false)->andReturn([]);

        $this->app->instance('saml2.test.auth', $auth);

        $response = $this->get("/saml2/{$tenant->uuid}/sls");

        $response->assertRedirect('/signed-out');
    }

    public function testSlsRouteUsesRetrieveParametersFromServerConfiguration(): void
    {
        $tenant = Tenant::query()->create($this->tenantAttributes());

        config([
            'saml2.logoutRoute' => '/signed-out',
            'saml2.retrieveParametersFromServer' => true,
        ]);

        $auth = \Mockery::mock(Auth::class);
        $auth->shouldReceive('sls')->once()->with(true)->andReturn([]);

        $this->app->instance('saml2.test.auth', $auth);

        $response = $this->get("/saml2/{$tenant->uuid}/sls");

        $response->assertRedirect('/signed-out');
    }

    public function testSlsRouteRedirectsToConfiguredErrorRouteAndFlashesErrors(): void
    {
        $tenant = Tenant::query()->create($this->tenantAttributes());

        config([
            'saml2.errorRoute' => '/sso/error',
            'saml2.logoutRoute' => '/signed-out',
        ]);

        $auth = \Mockery::mock(Auth::class);
        $auth->shouldReceive('sls')->once()->with(false)->andReturn(['logout_error']);
        $auth->shouldReceive('getLastErrorReason')->once()->andReturn('logout_validation_failed');
        $auth->shouldReceive('getTenant')->once()->andReturn($tenant);

        $this->app->instance('saml2.test.auth', $auth);

        $response = $this->get("/saml2/{$tenant->uuid}/sls");

        $response->assertRedirect('/sso/error');
        $response->assertSessionHas('saml2.error', ['logout_error']);
        $response->assertSessionHas('saml2.error_detail', ['logout_validation_failed']);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function tenantAttributes(array $overrides = []): array
    {
        return array_merge([
            'uuid' => (string) Str::uuid(),
            'key' => 'tenant-key',
            'idp_entity_id' => 'https://idp.example.com/entity',
            'idp_login_url' => 'https://idp.example.com/login',
            'idp_logout_url' => 'https://idp.example.com/logout',
            'idp_x509_cert' => 'BASE64_CERT_VALUE',
            'relay_state_url' => null,
            'name_id_format' => 'persistent',
            'metadata' => [],
        ], $overrides);
    }
}
