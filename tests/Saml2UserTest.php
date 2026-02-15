<?php

declare(strict_types=1);

namespace Slides\Saml2\Tests;

use PHPUnit\Framework\TestCase;
use Slides\Saml2\Models\Tenant;
use Slides\Saml2\Saml2User;

/**
 * @internal
 */
class Saml2UserTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testGetUserIdDelegatesToBaseAuth(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $oneLoginAuth->shouldReceive('getNameId')->once()->andReturn('user-id-1');

        $user = new Saml2User($oneLoginAuth, new Tenant());

        self::assertSame('user-id-1', $user->getUserId());
    }

    public function testGetNameIdDelegatesToBaseAuth(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $oneLoginAuth->shouldReceive('getNameId')->once()->andReturn('name-id-1');

        $user = new Saml2User($oneLoginAuth, new Tenant());

        self::assertSame('name-id-1', $user->getNameId());
    }

    public function testGetAttributesDelegatesToBaseAuth(): void
    {
        $attributes = ['email' => ['user@example.com']];
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $oneLoginAuth->shouldReceive('getAttributes')->once()->andReturn($attributes);

        $user = new Saml2User($oneLoginAuth, new Tenant());

        self::assertSame($attributes, $user->getAttributes());
    }

    public function testGetAttributesWithFriendlyNameDelegatesToBaseAuth(): void
    {
        $attributes = ['EmailAddress' => ['user@example.com']];
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $oneLoginAuth->shouldReceive('getAttributesWithFriendlyName')->once()->andReturn($attributes);

        $user = new Saml2User($oneLoginAuth, new Tenant());

        self::assertSame($attributes, $user->getAttributesWithFriendlyName());
    }

    public function testGetAttributeDelegatesToBaseAuth(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $oneLoginAuth->shouldReceive('getAttribute')
            ->once()
            ->with('urn:oid:0.9.2342.19200300.100.1.3')
            ->andReturn(['user@example.com']);

        $user = new Saml2User($oneLoginAuth, new Tenant());

        self::assertSame(
            ['user@example.com'],
            $user->getAttribute('urn:oid:0.9.2342.19200300.100.1.3'),
        );
    }

    public function testParseUserAttributeReturnsNullWhenAttributeIsEmpty(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $user = new Saml2User($oneLoginAuth, new Tenant());

        self::assertNull($user->parseUserAttribute());
        self::assertNull($user->parseUserAttribute(''));
    }

    public function testParseUserAttributeReturnsValueWhenPropertyNameIsMissing(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $oneLoginAuth->shouldReceive('getAttribute')
            ->once()
            ->with('urn:oid:0.9.2342.19200300.100.1.3')
            ->andReturn(['user@example.com']);

        $user = new Saml2User($oneLoginAuth, new Tenant());

        self::assertSame(
            ['user@example.com'],
            $user->parseUserAttribute('urn:oid:0.9.2342.19200300.100.1.3'),
        );
    }

    public function testParseUserAttributeStoresResolvedValueAsVirtualProperty(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $oneLoginAuth->shouldReceive('getAttribute')
            ->once()
            ->with('urn:oid:0.9.2342.19200300.100.1.3')
            ->andReturn(['user@example.com']);

        $user = new Saml2User($oneLoginAuth, new Tenant());
        $user->parseUserAttribute('urn:oid:0.9.2342.19200300.100.1.3', 'email');

        self::assertSame(['user@example.com'], $user->email);
        self::assertTrue(isset($user->email));
    }

    public function testParseAttributesStoresMultipleVirtualProperties(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $oneLoginAuth->shouldReceive('getAttribute')
            ->twice()
            ->andReturn(['user@example.com'], ['Test User']);

        $user = new Saml2User($oneLoginAuth, new Tenant());
        $user->parseAttributes([
            'email' => 'urn:oid:0.9.2342.19200300.100.1.3',
            'displayName' => 'urn:oid:2.16.840.1.113730.3.1.241',
        ]);

        self::assertSame(['user@example.com'], $user->email);
        self::assertSame(['Test User'], $user->displayName);
    }

    public function testGetSessionIndexDelegatesToBaseAuth(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $oneLoginAuth->shouldReceive('getSessionIndex')->once()->andReturn('session-index-1');

        $user = new Saml2User($oneLoginAuth, new Tenant());

        self::assertSame('session-index-1', $user->getSessionIndex());
    }

    public function testSetTenantUpdatesResolvedTenant(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $initialTenant = new Tenant();
        $updatedTenant = new Tenant();
        $updatedTenant->uuid = 'tenant-uuid-3';

        $user = new Saml2User($oneLoginAuth, $initialTenant);
        $user->setTenant($updatedTenant);

        self::assertSame($updatedTenant, $user->getTenant());
        self::assertSame('tenant-uuid-3', $user->getTenant()->uuid);
    }
}
