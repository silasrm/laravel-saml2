<?php

namespace Slides\Saml2\Tests;

use OneLogin\Saml2\Error as OneLoginError;
use PHPUnit\Framework\TestCase;
use Slides\Saml2\Auth;
use Slides\Saml2\Models\Tenant;

class Saml2AuthTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testIsAuthenticated()
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        $oneLoginAuth->shouldReceive('isAuthenticated')->andReturn(true);

        $this->assertTrue($saml2Auth->isAuthenticated());
    }

    public function testLogin()
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        $oneLoginAuth->shouldReceive('login')
            ->once()
            ->with(null, [], false, false, false, true)
            ->andReturn('https://idp.example.com/default-login-request');

        $result = $saml2Auth->login();

        $this->assertSame('https://idp.example.com/default-login-request', $result);
    }

    public function testLoginForwardsAllArguments()
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
                $expectedSetNameIdPolicy
            )
            ->andReturn('https://idp.example.com/login-request');

        $result = $saml2Auth->login(
            $expectedReturnTo,
            $expectedParameters,
            $expectedForceAuthn,
            $expectedIsPassive,
            $expectedStay,
            $expectedSetNameIdPolicy
        );

        $this->assertSame('https://idp.example.com/login-request', $result);
    }

    public function testLogout()
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

    public function testAcsError()
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());
        $oneLoginAuth->shouldReceive('processResponse')->once();
        $oneLoginAuth->shouldReceive('getErrors')->once()->andReturn(array('errors'));

        $error = $saml2Auth->acs();

        $this->assertSame(['errors'], $error);
    }


    public function testAcsNotAuthenticated()
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());
        $oneLoginAuth->shouldReceive('processResponse')->once();
        $oneLoginAuth->shouldReceive('getErrors')->once()->andReturn(null);
        $oneLoginAuth->shouldReceive('isAuthenticated')->once()->andReturn(false);
        $error =  $saml2Auth->acs();

        $this->assertSame(['error' => 'Could not authenticate'], $error);
    }


    public function testAcsOK()
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());
        $oneLoginAuth->shouldReceive('processResponse')->once();
        $oneLoginAuth->shouldReceive('getErrors')->once()->andReturn(null);
        $oneLoginAuth->shouldReceive('isAuthenticated')->once()->andReturn(true);

        $error =  $saml2Auth->acs();

        $this->assertNull($error);
    }

    public function testSlsError()
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());
        $oneLoginAuth->shouldReceive('processSLO')->once()->with(
            false,
            null,
            false,
            \Mockery::type('callable')
        );
        $oneLoginAuth->shouldReceive('getErrors')->once()->andReturn(['errors']);

        $error =  $saml2Auth->sls();

        $this->assertSame(['errors'], $error);
    }

    public function testSlsOK()
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());
        $oneLoginAuth->shouldReceive('processSLO')->once()->with(
            false,
            null,
            false,
            \Mockery::type('callable')
        );
        $oneLoginAuth->shouldReceive('getErrors')->once()->andReturn(null);

        $error =  $saml2Auth->sls();

        $this->assertNull($error);
    }

    public function testSlsCanRetrieveParametersFromServer()
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());
        $oneLoginAuth->shouldReceive('processSLO')->once()->with(
            false,
            null,
            true,
            \Mockery::type('callable')
        );
        $oneLoginAuth->shouldReceive('getErrors')->once()->andReturn(null);

        $error =  $saml2Auth->sls(true);

        $this->assertNull($error);
    }

    public function testCanGetLastError()
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        $oneLoginAuth->shouldReceive('getLastErrorReason')->andReturn('lastError');

        $this->assertSame('lastError', $saml2Auth->getLastErrorReason());
    }

    public function testCanGetLastMessageId()
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        $oneLoginAuth->shouldReceive('getLastMessageId')->once()->andReturn('message-id-123');

        $this->assertSame('message-id-123', $saml2Auth->getLastMessageId());
    }

    public function testGetBaseReturnsUnderlyingAuth()
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        $this->assertSame($oneLoginAuth, $saml2Auth->getBase());
    }

    public function testCanSetAndGetTenant()
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $initialTenant = new Tenant();
        $updatedTenant = new Tenant();
        $updatedTenant->uuid = 'tenant-uuid-2';
        $saml2Auth = new Auth($oneLoginAuth, $initialTenant);

        $saml2Auth->setTenant($updatedTenant);

        $this->assertSame($updatedTenant, $saml2Auth->getTenant());
        $this->assertSame('tenant-uuid-2', $saml2Auth->getTenant()->uuid);
    }

    public function testLogoutReturnsValueFromBaseAuth()
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());
        $oneLoginAuth->shouldReceive('logout')
            ->with(null, [], null, null, true, null, null)
            ->once()
            ->andReturn('https://idp.example.com/logout-request');

        $result = $saml2Auth->logout(null, null, null, null, true, null);

        $this->assertSame('https://idp.example.com/logout-request', $result);
    }

    public function testGetSaml2UserKeepsResolvedTenant()
    {
        $tenant = new Tenant();
        $tenant->uuid = 'tenant-uuid-1';
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $saml2Auth = new Auth($oneLoginAuth, $tenant);

        $user = $saml2Auth->getSaml2User();

        $this->assertSame($tenant, $user->getTenant());
        $this->assertSame('tenant-uuid-1', $user->getTenant()->uuid);
    }

    public function testGetMetadataReturnsMetadataWhenValid()
    {
        $metadata = '<xml>metadata</xml>';
        $settings = \Mockery::mock(\OneLogin\Saml2\Settings::class);
        $settings->shouldReceive('getSPMetadata')->once()->andReturn($metadata);
        $settings->shouldReceive('validateMetadata')->once()->with($metadata)->andReturn([]);

        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $oneLoginAuth->shouldReceive('getSettings')->once()->andReturn($settings);

        $saml2Auth = new Auth($oneLoginAuth, $this->mockTenant());

        $this->assertSame($metadata, $saml2Auth->getMetadata());
    }

    public function testGetMetadataThrowsWhenInvalid()
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
     *
     * @return \Slides\Saml2\Models\Tenant
     */
    protected function mockTenant()
    {
        return new \Slides\Saml2\Models\Tenant();
    }
}
