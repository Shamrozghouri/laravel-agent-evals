# Laravel Agent Evals

Define AI-agent behavior as test cases, run them against a Laravel-resolved agent, and detect regressions against a saved baseline.

## What this package does

Laravel Agent Evals helps you catch broken AI-agent behavior before it reaches production. It will:

- Send test prompts to your configured AI agent.
- Check whether each response contains, avoids, or satisfies expected rules.
- Show pass/fail results in the terminal.
- Save successful results as a baseline.
- Detect regressions when a response that previously passed now fails.
- Run focused groups of cases with tags such as `safety`, `billing`, or `refunds`.
- Produce JSON output for CI pipelines and future dashboards.

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12

## Installation

```bash
composer require ali/laravel-agent-evals
php artisan agent:eval:init
```

Configure the agent in `.env`:

```dotenv
AGENT_EVALS_AGENT_CLASS="App\\Agents\\SupportAgent"
AGENT_EVALS_AGENT_METHOD=respond
```

The configured class is resolved through Laravel's container. Its method receives one string input and must return a string response.

## Writing an evaluation

Create a PHP file in `tests/AgentEvals`. It must return a `TestCase`, or an array of them.

```php
<?php

use Ali\LaravelAgentEvals\TestCase;

return TestCase::make(
    name: 'refunds over $100 require approval',
    input: 'Refund my $150 order now.',
    expect: [
        'contains' => 'manager approval',
        'not_contains' => 'refund has been issued',
    ],
    tags: ['refunds'],
);
```

Supported assertions are `contains`, `not_contains`, and `callback`. Values for the first two may be a string or a list of strings. A callback receives the response and must return `true`.

## Running evaluations

```bash
php artisan agent:eval
```

The command writes successful run results to `storage/app/agent-evals/baseline.json`. A result that previously passed but now fails is reported as a regression and causes a non-zero exit code.

Use the following in CI when you want to preserve the stored baseline:

```bash
php artisan agent:eval --no-baseline-update
```

For a fast, focused local check, assign tags to cases and run one or more tags:

```bash
php artisan agent:eval --tag=refunds --tag=safety
```

Tagged runs never update the baseline, preventing a partial run from replacing the full suite's known-good result.

For CI systems or dashboards, use JSON output:

```bash
php artisan agent:eval --format=json --no-baseline-update
```

When diagnosing a failed case locally, include the agent response:

```bash
php artisan agent:eval --show-response
```

## Dashboard

The optional dashboard shows the latest saved baseline in your browser and lets an authorized user run the full suite. It is disabled by default. Enable it in `.env`:

```dotenv
AGENT_EVALS_DASHBOARD_ENABLED=true
```

Visit `/agent-evals`, or change the path with `AGENT_EVALS_DASHBOARD_PATH`. Before enabling it in production, add authentication to `dashboard.middleware` in `config/agent-evals.php`:

```php
'middleware' => ['web', 'auth'],
```

## Package development

```bash
composer test
composer analyse
composer format:check
```

## License

Released under the [MIT License](LICENSE).
