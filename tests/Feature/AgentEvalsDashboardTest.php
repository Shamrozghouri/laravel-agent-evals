<?php

namespace Ali\LaravelAgentEvals\Tests\Feature;

use Ali\LaravelAgentEvals\Tests\TestCase;

class AgentEvalsDashboardTest extends TestCase
{
    public function test_dashboard_is_available_when_enabled(): void
    {
        $this->get('/agent-evals')
            ->assertOk()
            ->assertSee('Laravel Agent Evals')
            ->assertSee('No baseline exists yet.');
    }
}
