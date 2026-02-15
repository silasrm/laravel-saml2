<?php declare(strict_types=1);

namespace Slides\Saml2\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Slides\Saml2\Models\Tenant;
use Slides\Saml2\Tests\TestCase;

class TenantCommandsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateTenantCommandPersistsTenant(): void
    {
        $this->artisan('saml2:create-tenant', [
            '--key' => 'acme',
            '--entityId' => 'https://idp.example.com/entity',
            '--loginUrl' => 'https://idp.example.com/login',
            '--logoutUrl' => 'https://idp.example.com/logout',
            '--x509cert' => 'BASE64_CERT_VALUE',
            '--metadata' => 'team:core,region:us',
        ])->assertExitCode(0);

        $tenant = Tenant::query()->where('key', 'acme')->first();

        $this->assertNotNull($tenant);
        $this->assertSame('https://idp.example.com/entity', $tenant->idp_entity_id);
        $this->assertSame('persistent', $tenant->name_id_format);
        $this->assertSame(['team' => 'core', 'region' => 'us'], $tenant->metadata);
    }

    public function testUpdateTenantCommandPreservesAndUpdatesNameIdFormat(): void
    {
        $tenant = Tenant::query()->create($this->tenantAttributes([
            'name_id_format' => 'unspecified',
        ]));

        $this->artisan('saml2:update-tenant', [
            'id' => $tenant->id,
            '--x509cert' => 'UPDATED_CERT',
        ])->assertExitCode(0);

        $tenant->refresh();
        $this->assertSame('unspecified', $tenant->name_id_format);
        $this->assertSame('UPDATED_CERT', $tenant->idp_x509_cert);

        $this->artisan('saml2:update-tenant', [
            'id' => $tenant->id,
            '--nameIdFormat' => 'transient',
        ])->assertExitCode(0);

        $tenant->refresh();
        $this->assertSame('transient', $tenant->name_id_format);
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
