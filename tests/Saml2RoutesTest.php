<?php

namespace Slides\Saml2\Tests;

use PHPUnit\Framework\TestCase;

class Saml2RoutesTest extends TestCase
{
    public function testSlsRouteAcceptsGetAndPost()
    {
        $routesFile = file_get_contents(__DIR__ . '/../src/Http/routes.php');

        $this->assertIsString($routesFile);
        $this->assertMatchesRegularExpression(
            "/Route::match\\(\\['GET',\\s*'POST'\\],\\s*'\\/\\{uuid\\}\\/sls'/",
            $routesFile
        );
    }
}
