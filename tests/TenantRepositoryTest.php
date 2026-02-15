<?php

declare(strict_types=1);

namespace Slides\Saml2\Tests;

use PHPUnit\Framework\TestCase;
use Slides\Saml2\Tests\Fakes\FakeQueryBuilder;
use Slides\Saml2\Tests\Fakes\FakeTenantRepository;

/**
 * @internal
 *
 * @coversNothing
 */
class TenantRepositoryTest extends TestCase
{
    public function testAllUsesWithTrashedByDefault(): void
    {
        $builder = new FakeQueryBuilder(['tenant-one', 'tenant-two']);
        $repository = new FakeTenantRepository($builder);

        $result = $repository->all();

        self::assertSame(['tenant-one', 'tenant-two'], $result);
        self::assertTrue($repository->lastWithTrashed);
        self::assertSame([
            ['get'],
        ], $builder->calls);
    }

    public function testFindByAnyIdentifierUsesIdForIntegerInput(): void
    {
        $builder = new FakeQueryBuilder(['tenant-id-10']);
        $repository = new FakeTenantRepository($builder);

        $result = $repository->findByAnyIdentifier(10, true);

        self::assertSame(['tenant-id-10'], $result);
        self::assertTrue($repository->lastWithTrashed);
        self::assertSame([
            ['where', 'id', 10],
            ['get'],
        ], $builder->calls);
    }

    public function testFindByAnyIdentifierUsesKeyAndUuidForStringInput(): void
    {
        $builder = new FakeQueryBuilder(['tenant-acme']);
        $repository = new FakeTenantRepository($builder);

        $result = $repository->findByAnyIdentifier('acme', false);

        self::assertSame(['tenant-acme'], $result);
        self::assertFalse($repository->lastWithTrashed);
        self::assertSame([
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

        self::assertSame('tenant-by-key', $result);
        self::assertFalse($repository->lastWithTrashed);
        self::assertSame([
            ['where', 'key', 'acme'],
            ['first'],
        ], $builder->calls);
    }

    public function testFindByIdUsesFirstResult(): void
    {
        $builder = new FakeQueryBuilder('tenant-by-id');
        $repository = new FakeTenantRepository($builder);

        $result = $repository->findById(11);

        self::assertSame('tenant-by-id', $result);
        self::assertTrue($repository->lastWithTrashed);
        self::assertSame([
            ['where', 'id', 11],
            ['first'],
        ], $builder->calls);
    }

    public function testFindByUUIDUsesFirstResult(): void
    {
        $builder = new FakeQueryBuilder('tenant-by-uuid');
        $repository = new FakeTenantRepository($builder);

        $result = $repository->findByUUID('tenant-uuid', false);

        self::assertSame('tenant-by-uuid', $result);
        self::assertFalse($repository->lastWithTrashed);
        self::assertSame([
            ['where', 'uuid', 'tenant-uuid'],
            ['first'],
        ], $builder->calls);
    }
}
