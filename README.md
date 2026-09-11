# Laravel Agent Evals

Define AI-agent behavior as test cases, run them against your real Laravel-resolved agent, and automatically catch regressions before they reach production.

Laravel Agent Evals lets you test how your AI agent actually responds to real prompts. Instead of relying only on traditional unit tests, you can define expected agent behavior, save known-good results as a baseline, and detect when future changes cause previously passing behavior to fail.

## Why Laravel Agent Evals?

AI-agent behavior can change when you modify:

* System prompts
* Agent instructions
* Models
* Tools
* Retrieval logic
* Business rules
* Application code
* External AI providers
* Context or knowledge sources

A normal unit test may confirm that your PHP code works while your AI agent starts giving an incorrect answer.

Laravel Agent Evals helps you catch these behavioral regressions before they ship.

---

## Features

* Define repeatable AI-agent evaluation cases
* Run evaluations against your real Laravel agent
* `contains` assertions
* `not_contains` assertions
* Custom `callback` assertions
* Tag evaluations such as `safety`, `billing`, or `refunds`
* Save successful results as a baseline
* Detect regressions against the saved baseline
* Run focused tagged evaluations
* JSON output for CI pipelines and dashboards
* Optional browser dashboard
* Show the actual agent response when debugging failures
* Laravel container-based agent resolution
* No changes required to your existing agent implementation

---

# Requirements

* PHP 8.1+
* Laravel 10+
* Composer

> **Compatibility note:** Laravel 10+ is the package's intended compatibility range. Always use the latest package release and check the CI matrix/release notes for the Laravel versions that have been explicitly tested.
>
> The package is designed to avoid unnecessarily restricting future Laravel versions, but a Laravel release should be considered officially verified only after it has been tested.

---

# Installation

Install the package as a development dependency:

```bash
composer require --dev shamrozghouri/laravel-agent-evals --dev
```

Then initialize the package:

```bash
php artisan agent:eval:init
```

The initialization command prepares the configuration and evaluation directory needed by the package.

---

# Quick Start

After installation:

```bash
composer require --dev shamrozghouri/laravel-agent-evals

php artisan agent:eval:init
```

Configure your agent in `.env`:

```env
AGENT_EVALS_AGENT_CLASS="App\\Agents\\SupportAgent"
AGENT_EVALS_AGENT_METHOD=respond
```

Create an evaluation inside:

```text
tests/AgentEvals/
```

Example:

```php
<?php

use Ali\LaravelAgentEvals\TestCase;

return TestCase::make(
    name: 'refund request requires approval',
    input: 'Refund my $150 order now.',
    expect: [
        'contains' => 'manager approval',
        'not_contains' => 'refund has been issued',
    ],
    tags: ['refunds'],
);
```

Run your evaluations:

```bash
php artisan agent:eval
```

That's it.

---

# How It Works

The package follows a simple flow:

```text
Your evaluation
      ↓
Your real Laravel agent
      ↓
Agent response
      ↓
Assertions
      ↓
Pass / Fail
      ↓
Saved baseline
      ↓
Future runs detect regressions
```

For example, suppose your support agent previously answered:

```text
A refund above $100 requires manager approval.
```

Your evaluation can define:

```php
'expect' => [
    'contains' => 'manager approval',
    'not_contains' => 'refund has been issued',
],
```

If a future change causes the agent to respond:

```text
Your $150 refund has been issued.
```

the evaluation fails.

That lets you catch behavioral regressions before deploying the change.

---

# 1. Configure Your Agent

Laravel Agent Evals resolves your agent through Laravel's service container.

Set the agent class in `.env`:

```env
AGENT_EVALS_AGENT_CLASS="App\\Agents\\SupportAgent"
AGENT_EVALS_AGENT_METHOD=respond
```

For example:

```php
<?php

namespace App\Agents;

class SupportAgent
{
    public function respond(string $input): string
    {
        return "Your request has been received.";
    }
}
```

The configured method receives the evaluation input as a string and must return a string response.

### Important

Your configured agent method should have this general behavior:

```php
$response = $agent->respond($input);
```

The response must be a string.

If your agent returns an object, array, stream, or another response type, convert the final result to the string representation you want to evaluate.

---

# 2. Create Your First Evaluation

Create a PHP file inside:

```text
tests/AgentEvals/
```

For example:

```text
tests/AgentEvals/refunds.php
```

Add:

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

You can also return multiple test cases from one file when appropriate.

---

# 3. Assertions

Laravel Agent Evals currently supports:

* `contains`
* `not_contains`
* `callback`

## contains

Use `contains` when the response must contain specific text.

```php
'expect' => [
    'contains' => 'manager approval',
],
```

You can also provide multiple expected strings:

```php
'expect' => [
    'contains' => [
        'manager approval',
        'refund policy',
    ],
],
```

The evaluation passes only when the expected content is found.

---

## not_contains

Use `not_contains` when the agent must avoid specific content.

```php
'expect' => [
    'not_contains' => 'refund has been issued',
],
```

Multiple values are supported:

```php
'expect' => [
    'not_contains' => [
        'refund has been issued',
        'refund is complete',
    ],
],
```

This is especially useful for safety and business-rule evaluations.

---

## callback

Use `callback` when you need custom validation logic.

```php
'expect' => [
    'callback' => function (string $response): bool {
        return str_contains($response, 'approval')
            && strlen($response) < 1000;
    },
],
```

The callback receives the agent response and must return:

```php
true
```

for a passing evaluation, or:

```php
false
```

for a failing evaluation.

Use callbacks when simple string assertions are not enough.

---

# 4. Tags

Tags let you organize evaluations into groups.

Example:

```php
return TestCase::make(
    name: 'refund requires approval',
    input: 'Refund my $150 order.',
    expect: [
        'contains' => 'approval',
    ],
    tags: ['refunds', 'billing'],
);
```

Another evaluation:

```php
tags: ['safety'],
```

You can then run only a specific group.

---

# 5. Run All Evaluations

Run:

```bash
php artisan agent:eval
```

The command discovers the evaluations in:

```text
tests/AgentEvals/
```

and executes them against your configured agent.

You will receive pass/fail results in the terminal.

---

# 6. Baselines

Baselines allow the package to remember a known-good evaluation result.

After a successful full evaluation run, results are saved to:

```text
storage/app/agent-evals/baseline.json
```

Think of the baseline as your agent's known-good behavior.

For example:

```text
Initial run
    ↓
All evaluations pass
    ↓
Baseline saved
```

Later:

```text
Agent/prompt/code changed
    ↓
Evaluations run again
    ↓
Previously passing case fails
    ↓
Regression detected
```

This makes it possible to detect behavioral changes over time.

---

# 7. Regression Detection

Suppose your baseline contains:

```text
refund-policy → PASS
billing-policy → PASS
safety-policy → PASS
```

Later, you change your agent prompt.

The next run produces:

```text
refund-policy → PASS
billing-policy → FAIL
safety-policy → PASS
```

The package identifies the previously passing evaluation as a regression.

The command exits with a non-zero status so CI systems can detect the failure.

---

# 8. CI Usage

For CI pipelines, you generally do not want a CI run to replace your known-good baseline.

Use:

```bash
php artisan agent:eval --no-baseline-update
```

This runs the evaluations while preserving the stored baseline.

Example GitHub Actions step:

```yaml
- name: Run Agent Evals
  run: php artisan agent:eval --no-baseline-update
```

If an evaluation fails, the command returns a non-zero exit code and the CI job can fail.

---

# 9. JSON Output

For CI systems, scripts, dashboards, or other tooling, use:

```bash
php artisan agent:eval --format=json --no-baseline-update
```

This produces machine-readable JSON output.

This is useful when you want another system to consume the evaluation results.

---

# 10. Run Specific Tags

Run only refund-related evaluations:

```bash
php artisan agent:eval --tag=refunds
```

Run multiple tags:

```bash
php artisan agent:eval --tag=refunds --tag=safety
```

Tagged runs are intended for focused checks and do not update the complete baseline.

This prevents a partial evaluation run from replacing the baseline for your entire evaluation suite.

---

# 11. Debugging a Failed Evaluation

When an evaluation fails, you may want to see exactly what your agent returned.

Use:

```bash
php artisan agent:eval --show-response
```

This is useful when you are trying to determine whether:

* Your prompt changed
* Your agent produced an unexpected response
* Your assertion is too strict
* Your expected text is incorrect
* Your agent returned an unexpected format

---

# 12. Dashboard

Laravel Agent Evals includes an optional dashboard for viewing the latest saved baseline and running the evaluation suite from a browser.

The dashboard is disabled by default.

Enable it in `.env`:

```env
AGENT_EVALS_DASHBOARD_ENABLED=true
```

The default dashboard path is:

```text
/agent-evals
```

You can change the path with:

```env
AGENT_EVALS_DASHBOARD_PATH=agent-evals
```

Then open:

```text
https://your-application.test/agent-evals
```

## Dashboard Security

If you enable the dashboard, protect it with authentication.

In:

```text
config/agent-evals.php
```

configure middleware such as:

```php
'middleware' => ['web', 'auth'],
```

### Important

Do not expose an evaluation dashboard publicly without appropriate authentication and authorization.

The dashboard can execute evaluation-related actions against your application, so treat it as an administrative feature.

---

# 13. Configuration

After initialization, review:

```text
config/agent-evals.php
```

The configuration controls package behavior such as:

* Agent class
* Agent method
* Dashboard
* Dashboard path
* Dashboard middleware
* Other evaluation settings

If you change configuration values, clear Laravel's cached configuration if your application uses config caching:

```bash
php artisan config:clear
```

If your production deployment uses cached configuration:

```bash
php artisan config:cache
```

---

# 14. Recommended Project Structure

A typical project can look like this:

```text
your-laravel-app/
├── app/
│   └── Agents/
│       └── SupportAgent.php
│
├── config/
│   └── agent-evals.php
│
├── tests/
│   └── AgentEvals/
│       ├── refunds.php
│       ├── billing.php
│       └── safety.php
│
├── storage/
│   └── app/
│       └── agent-evals/
│           └── baseline.json
│
├── .env
└── composer.json
```

---

# 15. A Complete Example

### Agent

```php
<?php

namespace App\Agents;

class SupportAgent
{
    public function respond(string $input): string
    {
        if (str_contains(strtolower($input), 'refund')) {
            return 'Refunds over $100 require manager approval.';
        }

        return 'Please contact our support team for assistance.';
    }
}
```

### `.env`

```env
AGENT_EVALS_AGENT_CLASS="App\\Agents\\SupportAgent"
AGENT_EVALS_AGENT_METHOD=respond
```

### Evaluation

Create:

```text
tests/AgentEvals/refunds.php
```

```php
<?php

use Ali\LaravelAgentEvals\TestCase;

return TestCase::make(
    name: 'refunds over $100 require approval',
    input: 'I want a refund for my $150 order.',
    expect: [
        'contains' => 'manager approval',
        'not_contains' => 'refund has been issued',
    ],
    tags: ['refunds', 'billing'],
);
```

### Run

```bash
php artisan agent:eval
```

### Debug

```bash
php artisan agent:eval --show-response
```

### CI

```bash
php artisan agent:eval --no-baseline-update
```

---

# Troubleshooting

If something does not work after installation, go through the following checklist before opening an issue.

## "There are no evaluations to run"

Make sure your evaluation files are inside:

```text
tests/AgentEvals/
```

Also make sure your PHP file returns a `TestCase` or an array of test cases.

Example:

```php
return TestCase::make(
    name: 'my test',
    input: 'Hello',
    expect: [
        'contains' => 'Hello',
    ],
);
```

---

## "Agent class not found"

Check:

```env
AGENT_EVALS_AGENT_CLASS="App\\Agents\\SupportAgent"
```

Make sure:

1. The PHP class actually exists.
2. The namespace is correct.
3. The class is autoloadable by Composer.
4. The class name in `.env` uses the complete namespace.
5. You have run Composer's autoloader if you recently created/moved classes:

```bash
composer dump-autoload
```

---

## "Method not found"

Check:

```env
AGENT_EVALS_AGENT_METHOD=respond
```

Then make sure your class contains:

```php
public function respond(string $input): string
{
    // ...
}
```

The method name in `.env` must exactly match the method in your agent.

---

## "Method must return a string"

Your configured agent method must return a string.

Incorrect:

```php
public function respond(string $input)
{
    return [
        'message' => 'Hello',
    ];
}
```

Correct:

```php
public function respond(string $input): string
{
    return 'Hello';
}
```

If your real agent returns a structured response, convert the final result into the string you want the evaluator to test.

---

## "Class not found after creating the agent"

Run:

```bash
composer dump-autoload
```

Then try again:

```bash
php artisan agent:eval
```

---

## "Configuration changes are not taking effect"

Clear the configuration cache:

```bash
php artisan config:clear
```

Then run:

```bash
php artisan agent:eval
```

---

## "My evaluation is failing even though the agent looks correct"

First inspect the actual response:

```bash
php artisan agent:eval --show-response
```

Then compare the actual response with your assertion.

For example, if your assertion is:

```php
'contains' => 'manager approval',
```

but the agent says:

```text
A manager needs to approve this refund.
```

the assertion may not match the exact expected phrase.

Consider whether your evaluation is too strict or whether the expected behavior should be changed.

---

## "My tagged evaluation is not updating the baseline"

This is expected.

Tagged runs are intended for focused checks and do not replace the full evaluation baseline.

Run the complete suite when you want to update the baseline:

```bash
php artisan agent:eval
```

---

## "CI passes locally but fails in GitHub Actions"

Check that CI is using:

* The same PHP version
* The same Laravel version
* The same Composer dependencies
* The same environment variables
* The same evaluation files
* The expected baseline

For CI, use:

```bash
php artisan agent:eval --no-baseline-update
```

If you need machine-readable output:

```bash
php artisan agent:eval --format=json --no-baseline-update
```

---

## "The dashboard does not open"

First check:

```env
AGENT_EVALS_DASHBOARD_ENABLED=true
```

Then clear config:

```bash
php artisan config:clear
```

Check the configured path:

```env
AGENT_EVALS_DASHBOARD_PATH=agent-evals
```

Then visit:

```text
/agent-evals
```

Also make sure the dashboard route is not being blocked by your middleware configuration.

---

## "I installed the package but Artisan does not recognize the command"

Run:

```bash
php artisan list
```

Look for:

```text
agent:eval
agent:eval:init
```

If they are missing:

```bash
composer dump-autoload
php artisan optimize:clear
```

Then try:

```bash
php artisan agent:eval:init
```

If the commands are still unavailable, verify that Composer actually installed the package:

```bash
composer show shamrozghouri/laravel-agent-evals
```

---

# Verify the Installation

After installing the package, this command is useful:

```bash
composer show shamrozghouri/laravel-agent-evals
```

You should see the installed package version and dependency information.

You can also check Laravel:

```bash
php artisan --version
```

And PHP:

```bash
php --version
```

---

# Updating the Package

To update to the latest compatible version:

```bash
composer update shamrozghouri/laravel-agent-evals
```

Or update all Composer dependencies:

```bash
composer update
```

After updating, run your evaluations again:

```bash
php artisan agent:eval
```

If you use the package in CI, test the new version before deploying it to production.

---

# Common First-Time Setup Checklist

After installing the package, follow this checklist:

```text
[ ] Package installed with Composer
[ ] php artisan agent:eval:init completed
[ ] config/agent-evals.php exists
[ ] AGENT_EVALS_AGENT_CLASS configured
[ ] AGENT_EVALS_AGENT_METHOD configured
[ ] Agent class exists
[ ] Agent method exists
[ ] Agent method returns a string
[ ] tests/AgentEvals contains evaluation files
[ ] php artisan agent:eval runs successfully
[ ] Baseline is generated
[ ] A deliberately failing evaluation has been tested
[ ] CI uses --no-baseline-update
[ ] Dashboard is protected if enabled
```

---

# Recommended CI Workflow

A recommended workflow is:

### Local development

Run focused evaluations:

```bash
php artisan agent:eval --tag=safety
```

### Before committing

Run the complete suite:

```bash
php artisan agent:eval
```

### CI

Run:

```bash
php artisan agent:eval --no-baseline-update
```

### Debugging

Run:

```bash
php artisan agent:eval --show-response
```

### Machine-readable CI output

Run:

```bash
php artisan agent:eval --format=json --no-baseline-update
```

---

# Package Development

If you are contributing to Laravel Agent Evals itself, install the repository dependencies:

```bash
composer install
```

Run tests:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

Check formatting:

```bash
composer format:check
```

Before submitting changes, make sure all checks pass.

---

# Compatibility

Laravel Agent Evals aims to support modern Laravel versions without unnecessarily restricting future releases.

The package Composer constraints are intentionally designed to avoid requiring a new package release merely because Laravel increments its major version, provided the package remains compatible with the underlying Illuminate APIs.

However:

> Composer dependency compatibility does not automatically guarantee runtime compatibility.

If a future Laravel release introduces a breaking API change that affects this package, that release must be tested and the package may need an update.

Always test your application and evaluation suite after upgrading Laravel.

---

# Roadmap

Potential future improvements include:

* LLM-as-a-judge scoring
* Additional evaluation strategies
* More advanced scoring
* Improved reports
* Expanded CI integrations
* More dashboard analytics
* Additional regression analysis

The current MVP focuses on deterministic, understandable assertions and baseline-based regression detection.

---

# Security

If you discover a security issue, please do not publicly disclose sensitive details in an issue.

Instead, contact the package maintainer privately with enough information to reproduce and investigate the issue.

Never expose your production API keys, `.env` file, authentication credentials, or private agent data when reporting a problem.

---

# Support / Troubleshooting Information

When reporting a problem, provide the following information whenever possible:

```bash
php --version
php artisan --version
composer show shamrozghouri/laravel-agent-evals
```

Also include:

* Laravel version
* PHP version
* Package version
* The Artisan command you ran
* The complete error message
* A minimal example of the evaluation that fails

Do **not** include:

* API keys
* Passwords
* `.env` contents containing secrets
* Private customer data
* Production credentials

---

# License

Laravel Agent Evals is open-sourced software licensed under the MIT License.
