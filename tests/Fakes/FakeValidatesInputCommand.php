<?php

declare(strict_types=1);

namespace Slides\Saml2\Tests\Fakes;

use Slides\Saml2\Commands\ValidatesInput;

class FakeValidatesInputCommand
{
    use ValidatesInput;

    /** @var array */
    private $options;

    /** @var string|null */
    public $lastErrorMessage;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function resolve(): ?string
    {
        return $this->resolveNameIdFormat();
    }

    public function resolveFromOption(string $option): ?string
    {
        return $this->resolveNameIdFormat($option);
    }

    protected function option($key)
    {
        return $this->options[$key] ?? null;
    }

    protected function error($message)
    {
        $this->lastErrorMessage = $message;
    }
}
