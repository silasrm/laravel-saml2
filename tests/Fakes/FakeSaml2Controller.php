<?php

namespace Slides\Saml2\Tests\Fakes;

use Slides\Saml2\Http\Controllers\Saml2Controller;

class FakeSaml2Controller extends Saml2Controller
{
    /**
     * @param string|null $url
     * @param string|null $fallback
     *
     * @return string
     */
    public function resolveTarget($url = null, $fallback = null)
    {
        return $this->resolveRedirectTarget($url, $fallback);
    }
}
