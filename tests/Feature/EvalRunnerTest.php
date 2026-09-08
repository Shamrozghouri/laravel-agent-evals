<?php

namespace Ali\LaravelAgentEvals\Tests\Feature;

use Ali\LaravelAgentEvals\EvalRunner;
use Ali\LaravelAgentEvals\Tests\TestCase;

class EvalRunnerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'agent-evals.agent.class',
            RunnerTestAgent::class
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

    public function test_runner_executes_agent_eval_and_returns_passing_result(): void
    {
        $runner = app(EvalRunner::class);

        $report = $runner->run();

        $this->assertCount(1, $report['results']);
        $this->assertTrue($report['results'][0]->passed);
        $this->assertSame(
            'Hello back! Hello',
            $report['results'][0]->response
        );
    }
}

class RunnerTestAgent
{
    public function respond(string $input): string
    {
        return 'Hello back! '.$input;
    }
}
