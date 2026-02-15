<?php

declare(strict_types=1);

namespace Slides\Saml2\Tests\Fakes;

use Slides\Saml2\Commands\UpdateTenant;
use Slides\Saml2\Models\Tenant;
use Slides\Saml2\Repositories\TenantRepository;

class FakeUpdateTenantCommand extends UpdateTenant
{
    /** @var array */
    private $arguments;

    /** @var array */
    private $options;

    /** @var array<string> */
    public $errors = [];

    /** @var array<string> */
    public $infos = [];

    public $lastRenderedTenant;

    public function __construct(TenantRepository $tenants, array $arguments = [], array $options = [])
    {
        parent::__construct($tenants);

        $this->arguments = $arguments;
        $this->options = $options;
        $this->output = new class {
            public function newLine($count = 1) {}
        };
    }

    public function argument($key = null)
    {
        return $this->arguments[$key] ?? null;
    }

    public function option($key = null)
    {
        return $this->options[$key] ?? null;
    }

    public function error($string, $verbosity = null)
    {
        $this->errors[] = (string) $string;
    }

    public function info($string, $verbosity = null)
    {
        $this->infos[] = (string) $string;
    }

    protected function renderTenantCredentials(Tenant $tenant)
    {
        $this->lastRenderedTenant = $tenant;
    }
}
