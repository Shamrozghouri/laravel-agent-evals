<?php

namespace Ali\LaravelAgentEvals\Tests\Feature;

use Ali\LaravelAgentEvals\EvalRunner;
use Ali\LaravelAgentEvals\Tests\TestCase;

class EvalRunnerBindingTest extends TestCase
{
    public function test_eval_runner_is_registered_in_the_container(): void
    {
        config()->set('agent-evals.agent.class', TestAgent::class);

        $runner = app(EvalRunner::class);

        $this->assertInstanceOf(EvalRunner::class, $runner);
    }
}

class TestAgent
{
    public function respond(string $input): string
    {
        return 'Test response: '.$input;
    }
}
