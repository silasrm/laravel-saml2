<?php

namespace Slides\Saml2\Tests;

use PHPUnit\Framework\TestCase;
use Slides\Saml2\Tests\Fakes\FakeQueryBuilder;
use Slides\Saml2\Tests\Fakes\FakeTenantRepository;

class TenantRepositoryTest extends TestCase
{
    public function testAllUsesWithTrashedByDefault(): void
    {
        $builder = new FakeQueryBuilder(['tenant-one', 'tenant-two']);
        $repository = new FakeTenantRepository($builder);

        $result = $repository->all();

        $this->assertSame(['tenant-one', 'tenant-two'], $result);
        $this->assertTrue($repository->lastWithTrashed);
        $this->assertSame([
            ['get'],
        ], $builder->calls);
    }

    public function testFindByAnyIdentifierUsesIdForIntegerInput(): void
    {
        $builder = new FakeQueryBuilder(['tenant-id-10']);
        $repository = new FakeTenantRepository($builder);

        $result = $repository->findByAnyIdentifier(10, true);

        $this->assertSame(['tenant-id-10'], $result);
        $this->assertTrue($repository->lastWithTrashed);
        $this->assertSame([
            ['where', 'id', 10],
            ['get'],
        ], $builder->calls);
    }

    public function testFindByAnyIdentifierUsesKeyAndUuidForStringInput(): void
    {
        $builder = new FakeQueryBuilder(['tenant-acme']);
        $repository = new FakeTenantRepository($builder);

        $result = $repository->findByAnyIdentifier('acme', false);

        $this->assertSame(['tenant-acme'], $result);
        $this->assertFalse($repository->lastWithTrashed);
        $this->assertSame([
            ['where', 'key', 'acme'],
            ['orWhere', 'uuid', 'acme'],
            ['get'],
        ], $builder->calls);
    }

    public function testFindByKeyUsesFirstResult(): void
    {
        $builder = new FakeQueryBuilder('tenant-by-key');
        $repository = new FakeTenantRepository($builder);

        $result = $repository->findByKey('acme', false);

        $this->assertSame('tenant-by-key', $result);
        $this->assertFalse($repository->lastWithTrashed);
        $this->assertSame([
            ['where', 'key', 'acme'],
            ['first'],
        ], $builder->calls);
    }

    public function testFindByIdUsesFirstResult(): void
    {
        $builder = new FakeQueryBuilder('tenant-by-id');
        $repository = new FakeTenantRepository($builder);

        $result = $repository->findById(11);

        $this->assertSame('tenant-by-id', $result);
        $this->assertTrue($repository->lastWithTrashed);
        $this->assertSame([
            ['where', 'id', 11],
            ['first'],
        ], $builder->calls);
    }

    public function testFindByUUIDUsesFirstResult(): void
    {
        $builder = new FakeQueryBuilder('tenant-by-uuid');
        $repository = new FakeTenantRepository($builder);

        $result = $repository->findByUUID('tenant-uuid', false);

        $this->assertSame('tenant-by-uuid', $result);
        $this->assertFalse($repository->lastWithTrashed);
        $this->assertSame([
            ['where', 'uuid', 'tenant-uuid'],
            ['first'],
        ], $builder->calls);
    }
}
