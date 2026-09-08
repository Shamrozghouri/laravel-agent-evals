<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Agent Resolver
    |--------------------------------------------------------------------------
    |
    | Tell the package how to call your actual AI agent. "class" is any
    | class Laravel can resolve out of the container (e.g. an Action,
    | a Service class, an Agent wrapper around your LLM client). "method"
    | is the method that will be called for every test case. It must
    | accept a single string $input argument and return a string response.
    |
    | Example:
    |   class SupportAgent {
    |       public function respond(string $input): string { ... }
    |   }
    |
    |   'class'  => \App\Agents\SupportAgent::class,
    |   'method' => 'respond',
    |
    */
    'agent' => [
        'class' => env('AGENT_EVALS_AGENT_CLASS', null),
        'method' => env('AGENT_EVALS_AGENT_METHOD', 'respond'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Case Path
    |--------------------------------------------------------------------------
    |
    | Where the package looks for test case files. Each file returns an
    | instance (or array of instances) of Ali\LaravelAgentEvals\TestCase.
    |
    */
    'test_path' => base_path('tests/AgentEvals'),

    /*
    |--------------------------------------------------------------------------
    | Baseline Storage
    |--------------------------------------------------------------------------
    |
    | Path to the JSON file used to store the last known-good run. This
    | file is read before each run (to detect regressions) and overwritten
    | after each run (unless --no-baseline-update is passed to the command).
    |
    */
    'baseline_path' => storage_path('app/agent-evals/baseline.json'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | An optional in-application dashboard for inspecting the current baseline
    | and running evals. It is disabled by default so it cannot accidentally be
    | exposed in production. Add the "auth" middleware in applications where
    | only signed-in users should access it.
    |
    */
    'dashboard' => [
        'enabled' => env('AGENT_EVALS_DASHBOARD_ENABLED', false),
        'path' => env('AGENT_EVALS_DASHBOARD_PATH', 'agent-evals'),
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Judge Scorer (not active in MVP)
    |--------------------------------------------------------------------------
    |
    | Stubbed for the next build layer (see spec §6.2). When enabled, a
    | "judge" assertion type will send the response + a rubric to the
    | configured class/method and expect a pass/fail + reasoning back,
    | instead of relying on exact contains/not_contains string matching.
    |
    | This is intentionally inert at MVP stage — wiring it into
    | EvalRunner::assert() is a future step, not part of the current build.
    |
    */
    'judge' => [
        'enabled' => false,
        'class' => env('AGENT_EVALS_JUDGE_CLASS', null),
        'method' => env('AGENT_EVALS_JUDGE_METHOD', 'score'),
    ],

];
