# Laravel Agent Evals

Define AI-agent behavior as test cases, run them against your Laravel agent, and detect regressions against a saved baseline.

## What this package does

Laravel Agent Evals helps you catch broken AI-agent behavior before it reaches production.

It lets you:

* Send test prompts to your configured AI agent.
* Check whether responses contain or avoid expected text.
* Add custom callback assertions for more advanced checks.
* See pass/fail results in the terminal.
* Save successful results as a baseline.
* Detect regressions when a previously passing case starts failing.
* Run focused evaluation cases using tags such as `safety`, `billing`, or `refunds`.
* Produce JSON output for CI pipelines and automation.
* Optionally use the dashboard to view and run evaluations from a browser.

## Requirements

* PHP 8.1+
* Laravel 10, 11, or 12

## Installation

Install the package in your Laravel application:

```bash
composer require shamrozghouri/laravel-agent-evals
```

Initialize the package:

```bash
php artisan agent:eval:init
```

## Configuration

Configure the agent that you want to evaluate in your `.env` file:

```dotenv
AGENT_EVALS_AGENT_CLASS="App\\Agents\\SupportAgent"
AGENT_EVALS_AGENT_METHOD=respond
```

The configured agent class is resolved through Laravel's service container.

The configured method receives one string input and must return a string response.

## Writing an Evaluation

Create a PHP file in:

```text
tests/AgentEvals
```

The file should return a `TestCase` or an array of test cases.

Example:

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

### Supported Assertions

The package currently supports:

* `contains`
* `not_contains`
* `callback`

The `contains` and `not_contains` values can be a string or a list of strings.

A callback receives the agent response and must return `true` or `false`.

Example:

```php
<?php

use Ali\LaravelAgentEvals\TestCase;

return TestCase::make(
    name: 'refund amount is mentioned',
    input: 'Can I get a refund for my order?',
    expect: [
        'callback' => fn (string $response): bool =>
            str_contains($response, '$'),
    ],
);
```

## Running Evaluations

Run the complete evaluation suite:

```bash
php artisan agent:eval
```

The command saves successful results to:

```text
storage/app/agent-evals/baseline.json
```

If a test case previously passed but now fails, it is reported as a regression and the command exits with a non-zero status.

This makes it suitable for CI pipelines.

## Prevent Baseline Updates

When running evaluations in CI, you can preserve the existing baseline:

```bash
php artisan agent:eval --no-baseline-update
```

This allows the current results to be compared against the known-good baseline without replacing it.

## Run Tagged Evaluations

Assign tags to your evaluation cases:

```php
return TestCase::make(
    name: 'refund approval',
    input: 'Refund my $150 order.',
    expect: [
        'contains' => 'manager approval',
    ],
    tags: ['refunds', 'billing'],
);
```

Then run specific tags:

```bash
php artisan agent:eval --tag=refunds --tag=safety
```

Tagged runs do not update the baseline. This prevents a partial evaluation run from replacing the baseline for the complete suite.

## JSON Output

For CI systems, scripts, or other automation, use JSON output:

```bash
php artisan agent:eval --format=json --no-baseline-update
```

This makes it easier to consume evaluation results programmatically.

## Show Agent Responses

When debugging a failed evaluation locally, use:

```bash
php artisan agent:eval --show-response
```

This includes the agent response in the terminal output and can help identify why an evaluation failed.

## Dashboard

Laravel Agent Evals can optionally provide a browser-based dashboard for viewing the latest saved baseline and running the full evaluation suite.

The dashboard is disabled by default.

Enable it in `.env`:

```dotenv
AGENT_EVALS_DASHBOARD_ENABLED=true
```

The default dashboard path is:

```text
/agent-evals
```

You can change the path with:

```dotenv
AGENT_EVALS_DASHBOARD_PATH=agent-evals
```

### Dashboard Authentication

Before enabling the dashboard in production, protect it with your application's authentication middleware.

In `config/agent-evals.php`:

```php
'middleware' => ['web', 'auth'],
```

Use the authentication and authorization rules appropriate for your application.

## CI

The package can be used in continuous integration to prevent AI-agent behavior regressions from reaching production.

A typical CI command is:

```bash
php artisan agent:eval --format=json --no-baseline-update
```

The command returns a non-zero exit code when a regression is detected, allowing the CI job to fail automatically.

## Package Development

Clone the repository and install the development dependencies:

```bash
composer install
```

Run the test suite:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

Check code formatting:

```bash
composer format:check
```

Format the code:

```bash
composer format
```

## How Baselines Work

Laravel Agent Evals stores the result of successful evaluations as a baseline.

On a later run, the package compares the current results against that baseline.

A regression occurs when:

1. An evaluation previously passed.
2. The same evaluation now fails.

When this happens, the regression is reported and the command exits with a non-zero status.

This allows AI-agent behavior changes to be detected automatically during development and CI.

## License

Released under the [MIT License](LICENSE).
