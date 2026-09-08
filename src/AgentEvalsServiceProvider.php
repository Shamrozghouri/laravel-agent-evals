<?php

namespace Ali\LaravelAgentEvals;

use Ali\LaravelAgentEvals\Console\Commands\AgentEvalCommand;
use Ali\LaravelAgentEvals\Console\Commands\AgentEvalInitCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class AgentEvalsServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/agent-evals.php',
            'agent-evals'
        );

        $this->app->singleton(BaselineStore::class, function ($app) {
            return new BaselineStore(
                config('agent-evals.baseline_path')
            );
        });

        $this->app->singleton(EvalRunner::class, function ($app) {
            $agentClass = config('agent-evals.agent.class');
            $agentMethod = config('agent-evals.agent.method', 'respond');

            if (! is_string($agentClass) || $agentClass === '') {
                throw new \RuntimeException(
                    'Agent evals agent class is not configured. '
                    .'Set AGENT_EVALS_AGENT_CLASS in your .env file.'
                );
            }

            if (! is_string($agentMethod) || $agentMethod === '') {
                throw new \RuntimeException(
                    'Agent evals agent method is not configured. '
                    .'Set AGENT_EVALS_AGENT_METHOD in your .env file.'
                );
            }

            $testPath = config('agent-evals.test_path', base_path('tests/AgentEvals'));

            if (! is_string($testPath) || $testPath === '') {
                throw new \RuntimeException('Agent evals test path is not configured.');
            }

            return new EvalRunner(
                container: $app,
                baselineStore: $app->make(BaselineStore::class),
                testPath: $testPath,
                agentClass: $agentClass,
                agentMethod: $agentMethod
            );
        });
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'agent-evals');

        $this->publishes([
            __DIR__.'/../config/agent-evals.php' => config_path('agent-evals.php'),
        ], 'agent-evals-config');

        if (config('agent-evals.dashboard.enabled', false)) {
            Route::middleware(config('agent-evals.dashboard.middleware', ['web']))
                ->prefix(config('agent-evals.dashboard.path', 'agent-evals'))
                ->group(__DIR__.'/../routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                AgentEvalCommand::class,
                AgentEvalInitCommand::class,
            ]);
        }
    }
}
