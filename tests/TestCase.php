<?php

namespace Ali\LaravelAgentEvals\Tests;

use Ali\LaravelAgentEvals\AgentEvalsServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=');
        $app['config']->set('agent-evals.dashboard.enabled', true);
        $app['config']->set('session.driver', 'array');
    }

    protected function getPackageProviders($app): array
    {
        return [
            AgentEvalsServiceProvider::class,
        ];
    }
}
