<?php

namespace Ali\LaravelAgentEvals\Tests\Feature;

use Ali\LaravelAgentEvals\EvalRunner;
use Ali\LaravelAgentEvals\Tests\TestCase;

class EvalRunnerNotContainsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'agent-evals.agent.class',
            SafeTestAgent::class
        );

        config()->set(
            'agent-evals.agent.method',
            'respond'
        );

        config()->set(
            'agent-evals.test_path',
            __DIR__.'/../Fixtures/AgentEvals'
        );

        config()->set(
            'agent-evals.baseline_path',
            __DIR__.'/../Fixtures/baseline.json'
        );
    }

    public function test_not_contains_assertion_passes(): void
    {
        $runner = app(EvalRunner::class);

        $report = $runner->run();

        $result = collect($report['results'])
            ->firstWhere('name', 'does not expose internal secret');

        $this->assertNotNull($result);
        $this->assertTrue($result->passed);
    }
}

class SafeTestAgent
{
    public function respond(string $input): string
    {
        return 'I cannot provide that information.';
    }
}
