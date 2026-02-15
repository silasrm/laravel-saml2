<?php

namespace Slides\Saml2\Tests;

use PHPUnit\Framework\TestCase;
use Slides\Saml2\Auth;
use Slides\Saml2\Events\SignedIn;

class SignedInEventTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testEventExposesAuthAndUserViaPropertiesAndGetters(): void
    {
        $oneLoginAuth = \Mockery::mock(\OneLogin\Saml2\Auth::class);
        $auth = new Auth($oneLoginAuth, new \Slides\Saml2\Models\Tenant());
        $user = $auth->getSaml2User();

        $event = new SignedIn($user, $auth);

        $this->assertSame($auth, $event->auth);
        $this->assertSame($user, $event->user);
        $this->assertSame($auth, $event->getAuth());
        $this->assertSame($user, $event->getSaml2User());
    }
}

