<?php

declare(strict_types=1);

namespace Slides\Saml2\Tests;

use PHPUnit\Framework\TestCase;
use Slides\Saml2\Tests\Fakes\FakeValidatesInputCommand;

/**
 * @internal
 *
 * @coversNothing
 */
class ValidatesInputTest extends TestCase
{
    public function testResolveNameIdFormatReturnsOptionWhenSupported(): void
    {
        $command = new FakeValidatesInputCommand(['nameIdFormat' => 'unspecified']);

        $resolved = $command->resolve();

        self::assertSame('unspecified', $resolved);
        self::assertNull($command->lastErrorMessage);
    }

    public function testResolveNameIdFormatReturnsDefaultPersistentWhenOptionMissing(): void
    {
        $command = new FakeValidatesInputCommand();

        $resolved = $command->resolve();

        self::assertSame('persistent', $resolved);
        self::assertNull($command->lastErrorMessage);
    }

    public function testResolveNameIdFormatReturnsNullWhenOptionIsInvalid(): void
    {
        $command = new FakeValidatesInputCommand(['nameIdFormat' => 'invalid-format']);

        $resolved = $command->resolve();

        self::assertNull($resolved);
        self::assertStringContainsString('Name ID format is invalid', (string) $command->lastErrorMessage);
        self::assertStringContainsString('persistent', (string) $command->lastErrorMessage);
    }

    public function testResolveNameIdFormatUsesProvidedOptionName(): void
    {
        $command = new FakeValidatesInputCommand(['format' => 'transient']);

        $resolved = $command->resolveFromOption('format');

        self::assertSame('transient', $resolved);
        self::assertNull($command->lastErrorMessage);
    }
}
