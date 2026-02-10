<?php

namespace Slides\Saml2\Tests;

use PHPUnit\Framework\TestCase;
use Slides\Saml2\Tests\Fakes\FakeQueryBuilder;
use Slides\Saml2\Tests\Fakes\FakeTenantRepository;

class TenantRepositoryTest extends TestCase
{
    public function testFindByAnyIdentifierUsesIdForIntegerInput()
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

    public function testFindByAnyIdentifierUsesKeyAndUuidForStringInput()
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
}
