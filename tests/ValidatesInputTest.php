<?php

namespace Slides\Saml2\Tests;

use PHPUnit\Framework\TestCase;
use Slides\Saml2\Tests\Fakes\FakeValidatesInputCommand;

class ValidatesInputTest extends TestCase
{
    public function testResolveNameIdFormatReturnsOptionWhenSupported(): void
    {
        $command = new FakeValidatesInputCommand(['nameIdFormat' => 'unspecified']);

        $resolved = $command->resolve();

        $this->assertSame('unspecified', $resolved);
        $this->assertNull($command->lastErrorMessage);
    }

    public function testResolveNameIdFormatReturnsDefaultPersistentWhenOptionMissing(): void
    {
        $command = new FakeValidatesInputCommand();

        $resolved = $command->resolve();

        $this->assertSame('persistent', $resolved);
        $this->assertNull($command->lastErrorMessage);
    }

    public function testResolveNameIdFormatReturnsNullWhenOptionIsInvalid(): void
    {
        $command = new FakeValidatesInputCommand(['nameIdFormat' => 'invalid-format']);

        $resolved = $command->resolve();

        $this->assertNull($resolved);
        $this->assertStringContainsString('Name ID format is invalid', (string) $command->lastErrorMessage);
        $this->assertStringContainsString('persistent', (string) $command->lastErrorMessage);
    }

    public function testResolveNameIdFormatUsesProvidedOptionName(): void
    {
        $command = new FakeValidatesInputCommand(['format' => 'transient']);

        $resolved = $command->resolveFromOption('format');

        $this->assertSame('transient', $resolved);
        $this->assertNull($command->lastErrorMessage);
    }
}
