<?php

declare(strict_types=1);

namespace Slides\Saml2\Tests;

use PHPUnit\Framework\TestCase;
use Slides\Saml2\Auth;
use Slides\Saml2\Events\SignedIn;

/**
 * @internal
 */
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

        self::assertSame($auth, $event->auth);
        self::assertSame($user, $event->user);
        self::assertSame($auth, $event->getAuth());
        self::assertSame($user, $event->getSaml2User());
    }
}
