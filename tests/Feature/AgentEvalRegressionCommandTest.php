<?php

namespace Ali\LaravelAgentEvals\Tests\Feature;

use Ali\LaravelAgentEvals\Tests\TestCase;

class AgentEvalRegressionCommandTest extends TestCase
{
    private string $baselinePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baselinePath = storage_path(
            'framework/testing/agent-evals-regression.json'
        );

        if (file_exists($this->baselinePath)) {
            unlink($this->baselinePath);
        }

        config()->set(
            'agent-evals.agent.class',
            RegressionCommandTestAgent::class
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
            $this->baselinePath
        );
    }

    protected function tearDown(): void
    {
        if (file_exists($this->baselinePath)) {
            unlink($this->baselinePath);
        }

        parent::tearDown();
    }

    public function test_regression_causes_command_to_fail(): void
    {
        file_put_contents(
            $this->baselinePath,
            json_encode([
                [
                    'name' => 'greets the user',
                    'passed' => true,
                    'response' => 'Hello back! Hello',
                    'reason' => null,
                    'tags' => [],
                ],
            ], JSON_PRETTY_PRINT)
        );

        $originalBaseline = file_get_contents($this->baselinePath);

        $this->artisan('agent:eval')
            ->expectsOutputToContain('1 regression(s) detected.')
            ->assertExitCode(1);

        $this->assertSame($originalBaseline, file_get_contents($this->baselinePath));
    }
}

class RegressionCommandTestAgent
{
    public function respond(string $input): string
    {
        return 'Goodbye! '.$input;
    }
}
