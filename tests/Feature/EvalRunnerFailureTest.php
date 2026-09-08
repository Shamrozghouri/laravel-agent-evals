<?php

namespace Ali\LaravelAgentEvals\Tests\Feature;

use Ali\LaravelAgentEvals\EvalRunner;
use Ali\LaravelAgentEvals\Tests\TestCase;

class EvalRunnerFailureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'agent-evals.agent.class',
            FailingTestAgent::class
        );

        config()->set(
            'agent-evals.agent.method',
            'respond'
        );

        config()->set(
            'agent-evals.test_path',
            __DIR__.'/../Fixtures/AgentEvals/Greeting'
        );

        config()->set(
            'agent-evals.baseline_path',
            __DIR__.'/../Fixtures/baseline.json'
        );
    }

    public function test_runner_returns_failed_result_when_assertion_fails(): void
    {
        $runner = app(EvalRunner::class);

        $report = $runner->run();

        $this->assertCount(1, $report['results']);
        $this->assertFalse($report['results'][0]->passed);
        $this->assertNotNull($report['results'][0]->reason);
    }
}

class FailingTestAgent
{
    public function respond(string $input): string
    {
        return 'Goodbye! '.$input;
    }
}
