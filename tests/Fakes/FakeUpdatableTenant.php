<?php

declare(strict_types=1);

namespace Slides\Saml2\Tests\Fakes;

use Slides\Saml2\Models\Tenant;

class FakeUpdatableTenant extends Tenant
{
    /** @var int */
    public $id = 1;

    /** @var string */
    public $uuid = 'tenant-uuid';

    /** @var string */
    public $name_id_format = 'unspecified';

    /** @var array<array> */
    public $updates = [];

    /** @var int */
    public $saveCalls = 0;

    public function update(array $attributes = [], array $options = [])
    {
        $this->updates[] = $attributes;

        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return bool
     */
    public function save(array $options = [])
    {
        ++$this->saveCalls;

        return true;
    }
}
