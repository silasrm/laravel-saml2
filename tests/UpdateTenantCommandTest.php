<?php declare(strict_types=1);

namespace Slides\Saml2\Tests;

use PHPUnit\Framework\TestCase;
use Slides\Saml2\Repositories\TenantRepository;
use Slides\Saml2\Tests\Fakes\FakeUpdatableTenant;
use Slides\Saml2\Tests\Fakes\FakeUpdateTenantCommand;

class UpdateTenantCommandTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testHandlePreservesExistingNameIdFormatWhenOptionIsMissing(): void
    {
        $tenant = new FakeUpdatableTenant();
        $tenants = \Mockery::mock(TenantRepository::class);
        $tenants->shouldReceive('findById')->once()->with(99)->andReturn($tenant);

        $command = new FakeUpdateTenantCommand(
            $tenants,
            ['id' => 99],
            ['x509cert' => 'NEW_CERT']
        );

        $command->handle();

        $this->assertCount(1, $tenant->updates);
        $this->assertSame('unspecified', $tenant->name_id_format);
        $this->assertArrayNotHasKey('name_id_format', $tenant->updates[0]);
        $this->assertSame('NEW_CERT', $tenant->updates[0]['idp_x509_cert']);
        $this->assertSame(1, $tenant->saveCalls);
        $this->assertEmpty($command->errors);
    }

    public function testHandleUpdatesNameIdFormatWhenOptionIsProvided(): void
    {
        $tenant = new FakeUpdatableTenant();
        $tenants = \Mockery::mock(TenantRepository::class);
        $tenants->shouldReceive('findById')->once()->with(100)->andReturn($tenant);

        $command = new FakeUpdateTenantCommand(
            $tenants,
            ['id' => 100],
            ['nameIdFormat' => 'transient', 'x509cert' => 'NEW_CERT']
        );

        $command->handle();

        $this->assertCount(1, $tenant->updates);
        $this->assertArrayHasKey('name_id_format', $tenant->updates[0]);
        $this->assertSame('transient', $tenant->updates[0]['name_id_format']);
        $this->assertSame('transient', $tenant->name_id_format);
        $this->assertSame(1, $tenant->saveCalls);
        $this->assertEmpty($command->errors);
    }

    public function testHandleDoesNotUpdateTenantWhenNameIdFormatIsInvalid(): void
    {
        $tenant = new FakeUpdatableTenant();
        $tenants = \Mockery::mock(TenantRepository::class);
        $tenants->shouldReceive('findById')->once()->with(101)->andReturn($tenant);

        $command = new FakeUpdateTenantCommand(
            $tenants,
            ['id' => 101],
            ['nameIdFormat' => 'invalid']
        );

        $command->handle();

        $this->assertSame([], $tenant->updates);
        $this->assertSame(0, $tenant->saveCalls);
        $this->assertNotEmpty($command->errors);
        $this->assertStringContainsString('Name ID format is invalid', $command->errors[0]);
    }
}
