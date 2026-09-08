<?php

namespace Ali\LaravelAgentEvals\Console\Commands;

use Ali\LaravelAgentEvals\EvalResult;
use Ali\LaravelAgentEvals\EvalRunner;
use Illuminate\Console\Command;
use JsonException;

final class AgentEvalCommand extends Command
{
    protected $signature = 'agent:eval
        {--no-baseline-update : Do not update the baseline after the run}
        {--tag=* : Run only cases matching one or more tags}
        {--format=table : Output format: table or json}
        {--show-response : Show full agent responses for failed cases}';

    protected $description = 'Run Laravel Agent Evals';

    public function handle(EvalRunner $runner): int
    {
        $format = $this->option('format');

        if (! in_array($format, ['table', 'json'], true)) {
            $this->components->error('The --format option must be either "table" or "json".');

            return self::INVALID;
        }

        $tags = $this->option('tag');

        if ($format === 'table') {
            $this->components->info('Running Laravel Agent Evals...');
        }

        try {
            $report = $runner->run($tags);
        } catch (\Throwable $e) {
            $this->renderError('Agent eval run failed: '.$e->getMessage(), $format);

            return self::FAILURE;
        }

        $results = $report['results'];
        $regressions = $report['regressions'];

        if ($results === []) {
            $this->renderError('No agent eval cases matched the selected tags.', $format);

            return self::FAILURE;
        }

        $passed = count(array_filter($results, fn (EvalResult $result): bool => $result->passed));
        $failed = count($results) - $passed;

        if ($format === 'json') {
            $this->writeJson([
                'results' => array_map(static fn (EvalResult $result): array => $result->toArray(), $results),
                'regressions' => array_map(static fn (EvalResult $result): array => $result->toArray(), $regressions),
                'summary' => [
                    'total' => count($results),
                    'passed' => $passed,
                    'failed' => $failed,
                    'regressions' => count($regressions),
                ],
            ]);
        } else {
            $this->renderTable($results);
        }

        if ($regressions !== []) {
            if ($format === 'table') {
                $this->components->error(count($regressions).' regression(s) detected.');

                foreach ($regressions as $regression) {
                    $this->line('  - '.$regression->name);
                }
            }

            return self::FAILURE;
        }

        if ($failed > 0) {
            return self::FAILURE;
        }

        if ($tags !== []) {
            if ($format === 'table') {
                $this->components->info('Baseline update skipped for a tagged run. Run the full suite to update it.');
            }

            return self::SUCCESS;
        }

        if (! $this->option('no-baseline-update')) {
            $runner->saveBaseline($results);

            if ($format === 'table') {
                $this->components->info('Baseline updated successfully.');
            }
        } elseif ($format === 'table') {
            $this->components->info('Baseline update skipped.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<EvalResult>  $results
     */
    private function renderTable(array $results): void
    {
        $this->newLine();

        foreach ($results as $result) {
            $this->components->twoColumnDetail(
                $result->name,
                $result->passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>'
            );

            if (! $result->passed && $result->reason !== null) {
                $this->line('  Reason: '.$result->reason);
            }

            if (! $result->passed && $this->option('show-response')) {
                $this->line('  Response: '.$result->response);
            }
        }

        $passed = count(array_filter($results, fn (EvalResult $result): bool => $result->passed));
        $failed = count($results) - $passed;

        $this->newLine();
        $this->line("Tests: {$passed} passed, {$failed} failed.");
    }

    private function renderError(string $message, string $format): void
    {
        if ($format === 'json') {
            $this->writeJson(['error' => $message]);

            return;
        }

        $this->components->error($message);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws JsonException
     */
    private function writeJson(array $data): void
    {
        $this->line((string) json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));
    }
}
