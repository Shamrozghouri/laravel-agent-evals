<?php

namespace Ali\LaravelAgentEvals\Tests\Feature;

use Ali\LaravelAgentEvals\EvalRunner;
use Ali\LaravelAgentEvals\Tests\TestCase;

class EvalRunnerExecutionFailureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('agent-evals.agent.class', ThrowingTestAgent::class);
        config()->set('agent-evals.test_path', __DIR__.'/../Fixtures/AgentEvals/Greeting');
        config()->set('agent-evals.baseline_path', sys_get_temp_dir().'/agent-evals-execution-baseline.json');
    }

    public function test_runner_captures_agent_exceptions_as_failed_results(): void
    {
        $result = app(EvalRunner::class)->run()['results'][0];

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('Agent execution failed: Agent unavailable.', $result->reason);
    }
}

class ThrowingTestAgent
{
    public function respond(string $input): string
    {
        throw new \RuntimeException('Agent unavailable.');
    }
}
