<?php

namespace Ali\LaravelAgentEvals\Tests\Feature;

use Ali\LaravelAgentEvals\EvalRunner;
use Ali\LaravelAgentEvals\Tests\TestCase;

class EvalRunnerCallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'agent-evals.agent.class',
            CallbackTestAgent::class
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

    public function test_callback_assertion_passes(): void
    {
        $runner = app(EvalRunner::class);

        $report = $runner->run();

        $result = collect($report['results'])
            ->firstWhere('name', 'response satisfies custom rule');

        $this->assertNotNull($result);
        $this->assertTrue($result->passed);
    }
}

class CallbackTestAgent
{
    public function respond(string $input): string
    {
        return 'The answer is 4.';
    }
}
