<?php

namespace Slides\Saml2\Tests\Fakes;

class FakeQueryBuilder
{
    /**
     * @var array
     */
    public $calls = [];

    /**
     * @var mixed
     */
    private $result;

    /**
     * @param mixed $result
     */
    public function __construct($result)
    {
        $this->result = $result;
    }

    public function where(string $field, $value)
    {
        $this->calls[] = ['where', $field, $value];

        return $this;
    }

    public function orWhere(string $field, $value)
    {
        $this->calls[] = ['orWhere', $field, $value];

        return $this;
    }

    public function get()
    {
        $this->calls[] = ['get'];

        return $this->result;
    }

    public function first()
    {
        $this->calls[] = ['first'];

        return $this->result;
    }
}
