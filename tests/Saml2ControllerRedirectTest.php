<?php

namespace Slides\Saml2\Tests;

use PHPUnit\Framework\TestCase;
use Slides\Saml2\Tests\Fakes\FakeSaml2Controller;

class Saml2ControllerRedirectTest extends TestCase
{
    public function testResolveRedirectTargetReturnsPrimaryUrlWhenProvided()
    {
        $controller = new FakeSaml2Controller();

        $resolved = $controller->resolveTarget('https://example.com/target', '/fallback');

        $this->assertSame('https://example.com/target', $resolved);
    }

    public function testResolveRedirectTargetReturnsFallbackWhenPrimaryIsMissing()
    {
        $controller = new FakeSaml2Controller();

        $resolved = $controller->resolveTarget(null, '/fallback');

        $this->assertSame('/fallback', $resolved);
    }

    public function testResolveRedirectTargetReturnsRootWhenValuesAreMissing()
    {
        $controller = new FakeSaml2Controller();

        $resolved = $controller->resolveTarget(null, null);

        $this->assertSame('/', $resolved);
    }
}
