<?php

namespace Ali\LaravelAgentEvals\Tests\Feature;

use Ali\LaravelAgentEvals\Tests\TestCase;

class AgentEvalCommandTest extends TestCase
{
    private string $baselinePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baselinePath = storage_path(
            'framework/testing/agent-evals-baseline.json'
        );

        if (file_exists($this->baselinePath)) {
            unlink($this->baselinePath);
        }

        config()->set(
            'agent-evals.agent.class',
            RunnerCommandTestAgent::class
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

    public function test_agent_eval_command_runs_and_creates_baseline(): void
    {
        $this->artisan('agent:eval')
            ->assertExitCode(0);

        $this->assertFileExists($this->baselinePath);

        $baseline = json_decode(
            file_get_contents($this->baselinePath),
            true
        );

        $this->assertIsArray($baseline);
        $this->assertCount(1, $baseline);
        $this->assertSame(
            'greets the user',
            $baseline[0]['name']
        );
        $this->assertTrue($baseline[0]['passed']);
    }
}

class RunnerCommandTestAgent
{
    public function respond(string $input): string
    {
        return 'Hello back! '.$input;
    }
}
