<?php

namespace Ali\LaravelAgentEvals\Tests\Feature;

use Ali\LaravelAgentEvals\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_package_commands_are_registered(): void
    {
        $this->artisan('list')
            ->assertExitCode(0);

        $this->artisan('agent:eval:init')
            ->assertExitCode(0);
    }
}
