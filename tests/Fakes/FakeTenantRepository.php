<?php

namespace Slides\Saml2\Tests\Fakes;

use Slides\Saml2\Repositories\TenantRepository;

class FakeTenantRepository extends TenantRepository
{
    /**
     * @var FakeQueryBuilder
     */
    private $builder;

    /**
     * @var bool|null
     */
    public $lastWithTrashed;

    public function __construct(FakeQueryBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function query(bool $withTrashed = false)
    {
        $this->lastWithTrashed = $withTrashed;

        return $this->builder;
    }
}
