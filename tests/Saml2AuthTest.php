<?php

declare(strict_types=1);

namespace Slides\Saml2\Tests;

use OneLogin\Saml2\Error as OneLoginError;
use PHPUnit\Framework\TestCase;
use Slides\Saml2\Auth;
use Slides\Saml2\Models\Tenant;

/**
 * @internal
 */
class Saml2AuthTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testIsAuthenticated(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        $oneLoginAuth->shouldReceive('isAuthenticated')->andReturn(true);

        self::assertTrue($saml2Auth->isAuthenticated());
    }

    public function testLogin(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        $oneLoginAuth->shouldReceive('login')
            ->once()
            ->with(null, [], false, false, false, true)
            ->andReturn('https://idp.example.com/default-login-request');

        $result = $saml2Auth->login();

        self::assertSame('https://idp.example.com/default-login-request', $result);
    }

    public function testLoginForwardsAllArguments(): void
    {
        $expectedReturnTo = 'https://example.com/dashboard';
        $expectedParameters = ['tenant' => 'acme'];
        $expectedForceAuthn = true;
        $expectedIsPassive = true;
        $expectedStay = true;
        $expectedSetNameIdPolicy = false;

        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        $oneLoginAuth->shouldReceive('login')
            ->once()
            ->with(
                $expectedReturnTo,
                $expectedParameters,
                $expectedForceAuthn,
                $expectedIsPassive,
                $expectedStay,
                $expectedSetNameIdPolicy,
            )
            ->andReturn('https://idp.example.com/login-request');

        $result = $saml2Auth->login(
            $expectedReturnTo,
            $expectedParameters,
            $expectedForceAuthn,
            $expectedIsPassive,
            $expectedStay,
            $expectedSetNameIdPolicy,
        );

        self::assertSame('https://idp.example.com/login-request', $result);
    }

    public function testLogout(): void
    {
        $expectedReturnTo = 'http://localhost';
        $expectedSessionIndex = 'session_index_value';
        $expectedNameId = 'name_id_value';
        $expectedNameIdFormat = 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified';
        $expectedStay = true;
        $expectedNameIdNameQualifier = 'name_id_name_qualifier';

        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        $oneLoginAuth->shouldReceive('logout')
            ->with($expectedReturnTo, [], $expectedNameId, $expectedSessionIndex, $expectedStay, $expectedNameIdFormat, $expectedNameIdNameQualifier)
            ->once();

        $saml2Auth->logout($expectedReturnTo, $expectedNameId, $expectedSessionIndex, $expectedNameIdFormat, $expectedStay, $expectedNameIdNameQualifier);

        $this->addToAssertionCount(1);
    }

    public function testAcsError(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());
        $oneLoginAuth->shouldReceive('processResponse')->once();
        $oneLoginAuth->shouldReceive('getErrors')->once()->andReturn(['errors']);

        $error = $saml2Auth->acs();

        self::assertSame(['errors'], $error);
    }

    public function testAcsNotAuthenticated(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());
        $oneLoginAuth->shouldReceive('processResponse')->once();
        $oneLoginAuth->shouldReceive('getErrors')->once()->andReturn(null);
        $oneLoginAuth->shouldReceive('isAuthenticated')->once()->andReturn(false);
        $error =  $saml2Auth->acs();

        self::assertSame(['error' => 'Could not authenticate'], $error);
    }

    public function testAcsOK(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());
        $oneLoginAuth->shouldReceive('processResponse')->once();
        $oneLoginAuth->shouldReceive('getErrors')->once()->andReturn(null);
        $oneLoginAuth->shouldReceive('isAuthenticated')->once()->andReturn(true);

        $error =  $saml2Auth->acs();

        self::assertNull($error);
    }

    public function testSlsError(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());
        $oneLoginAuth->shouldReceive('processSLO')->once()->with(
            false,
            null,
            false,
            \Mockery::type('callable'),
        );
        $oneLoginAuth->shouldReceive('getErrors')->once()->andReturn(['errors']);

        $error =  $saml2Auth->sls();

        self::assertSame(['errors'], $error);
    }

    public function testSlsOK(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());
        $oneLoginAuth->shouldReceive('processSLO')->once()->with(
            false,
            null,
            false,
            \Mockery::type('callable'),
        );
        $oneLoginAuth->shouldReceive('getErrors')->once()->andReturn(null);

        $error =  $saml2Auth->sls();

        self::assertNull($error);
    }

    public function testSlsCanRetrieveParametersFromServer(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());
        $oneLoginAuth->shouldReceive('processSLO')->once()->with(
            false,
            null,
            true,
            \Mockery::type('callable'),
        );
        $oneLoginAuth->shouldReceive('getErrors')->once()->andReturn(null);

        $error =  $saml2Auth->sls(true);

        self::assertNull($error);
    }

    public function testCanGetLastError(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        $oneLoginAuth->shouldReceive('getLastErrorReason')->andReturn('lastError');

        self::assertSame('lastError', $saml2Auth->getLastErrorReason());
    }

    public function testCanGetLastMessageId(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        $oneLoginAuth->shouldReceive('getLastMessageId')->once()->andReturn('message-id-123');

        self::assertSame('message-id-123', $saml2Auth->getLastMessageId());
    }

    public function testGetBaseReturnsUnderlyingAuth(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        self::assertSame($oneLoginAuth, $saml2Auth->getBase());
    }

    public function testCanSetAndGetTenant(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $initialTenant = new Tenant();
        $updatedTenant = new Tenant();
        $updatedTenant->uuid = 'tenant-uuid-2';
        $saml2Auth = new Auth($oneLoginAuth, $initialTenant);

        $saml2Auth->setTenant($updatedTenant);

        self::assertSame($updatedTenant, $saml2Auth->getTenant());
        self::assertSame('tenant-uuid-2', $saml2Auth->getTenant()->uuid);
    }

    public function testLogoutReturnsValueFromBaseAuth(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());
        $oneLoginAuth->shouldReceive('logout')
            ->with(null, [], null, null, true, null, null)
            ->once()
            ->andReturn('https://idp.example.com/logout-request');

        $result = $saml2Auth->logout(null, null, null, null, true, null);

        self::assertSame('https://idp.example.com/logout-request', $result);
    }

    public function testGetSaml2UserKeepsResolvedTenant(): void
    {
        $tenant = new Tenant();
        $tenant->uuid = 'tenant-uuid-1';
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $tenant);

        $user = $saml2Auth->getSaml2User();

        self::assertSame($tenant, $user->getTenant());
        self::assertSame('tenant-uuid-1', $user->getTenant()->uuid);
    }

    public function testGetMetadataReturnsMetadataWhenValid(): void
    {
        $metadata = '<xml>metadata</xml>';
        $settings = \Mockery::mock(\OneLogin\Saml2\Settings::class);
        $settings->shouldReceive('getSPMetadata')->once()->andReturn($metadata);
        $settings->shouldReceive('validateMetadata')->once()->with($metadata)->andReturn([]);

        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $oneLoginAuth->shouldReceive('getSettings')->once()->andReturn($settings);

        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        self::assertSame($metadata, $saml2Auth->getMetadata());
    }

    public function testGetMetadataThrowsWhenInvalid(): void
    {
        $metadata = '<xml>metadata</xml>';
        $settings = \Mockery::mock(\OneLogin\Saml2\Settings::class);
        $settings->shouldReceive('getSPMetadata')->once()->andReturn($metadata);
        $settings->shouldReceive('validateMetadata')->once()->with($metadata)->andReturn(['missing NameID']);

        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $oneLoginAuth->shouldReceive('getSettings')->once()->andReturn($settings);

        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(OneLoginError::METADATA_SP_INVALID);
        $this->expectExceptionMessage('Invalid SP metadata: missing NameID');

        $saml2Auth->getMetadata();
    }

    /**
     * Create a fake tenant.
     */
    protected function mockTenant(): Tenant
    {
        return new Tenant();
    }
}
