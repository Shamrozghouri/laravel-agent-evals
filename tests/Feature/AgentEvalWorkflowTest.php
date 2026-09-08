<?php

namespace Ali\LaravelAgentEvals\Tests\Feature;

use Ali\LaravelAgentEvals\EvalRunner;
use Ali\LaravelAgentEvals\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class AgentEvalWorkflowTest extends TestCase
{
    private string $baselinePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baselinePath = storage_path('framework/testing/agent-evals-workflow.json');

        if (file_exists($this->baselinePath)) {
            unlink($this->baselinePath);
        }

        config()->set('agent-evals.agent.class', WorkflowTestAgent::class);
        config()->set('agent-evals.agent.method', 'respond');
        config()->set('agent-evals.test_path', __DIR__.'/../Fixtures/AgentEvals/Tagged');
        config()->set('agent-evals.baseline_path', $this->baselinePath);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->baselinePath)) {
            unlink($this->baselinePath);
        }

        parent::tearDown();
    }

    public function test_runner_filters_cases_by_tag(): void
    {
        $report = app(EvalRunner::class)->run(['safety']);

        $this->assertCount(1, $report['results']);
        $this->assertSame('answers safety questions', $report['results'][0]->name);
    }

    public function test_tagged_runs_do_not_replace_the_full_baseline(): void
    {
        $this->artisan('agent:eval', ['--tag' => ['safety']])
            ->expectsOutputToContain('Baseline update skipped for a tagged run.')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->baselinePath);
    }

    public function test_json_format_is_machine_readable_for_ci(): void
    {
        $exitCode = Artisan::call('agent:eval', [
            '--format' => 'json',
            '--no-baseline-update' => true,
        ]);

        $output = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertSame(2, $output['summary']['total']);
        $this->assertSame(2, $output['summary']['passed']);
        $this->assertSame(0, $output['summary']['failed']);
    }
}

class WorkflowTestAgent
{
    public function respond(string $input): string
    {
        return 'Hello back! '.$input;
    }
}
