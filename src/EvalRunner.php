<?php

namespace Ali\LaravelAgentEvals;

use Closure;
use Illuminate\Contracts\Container\Container;
use RuntimeException;
use Throwable;

final class EvalRunner
{
    public function __construct(
        private readonly Container $container,
        private readonly BaselineStore $baselineStore,
        private readonly string $testPath,
        private readonly string $agentClass,
        private readonly string $agentMethod = 'respond',
    ) {}

    /**
     * Run all configured agent evaluation test cases.
     *
     * @param  list<string>  $tags
     * @return array{
     *     results: array<EvalResult>,
     *     regressions: array<EvalResult>
     * }
     */
    public function run(array $tags = []): array
    {
        $testCases = $this->loadTestCases();
        $baselineByName = [];

        foreach ($this->baselineStore->load() as $baselineResult) {
            $baselineByName[$baselineResult->name] = $baselineResult;
        }

        $results = [];
        $regressions = [];

        foreach ($testCases as $testCase) {
            if (! $this->matchesTags($testCase, $tags)) {
                continue;
            }

            $result = $this->runTestCase($testCase);

            $results[] = $result;

            if ($this->baselineStore->isRegression(
                $result,
                $baselineByName[$testCase->name] ?? null
            )) {
                $regressions[] = $result;
            }
        }

        return [
            'results' => $results,
            'regressions' => $regressions,
        ];
    }

    /**
     * Save the supplied results as the new baseline.
     *
     * @param  array<EvalResult>  $results
     */
    public function saveBaseline(array $results): void
    {
        $this->baselineStore->save($results);
    }

    /**
     * Execute one test case against the configured agent.
     */
    private function runTestCase(TestCase $testCase): EvalResult
    {
        try {
            $agent = $this->container->make($this->agentClass);

            if (! method_exists($agent, $this->agentMethod)) {
                throw new RuntimeException(
                    "Agent class {$this->agentClass} does not have method {$this->agentMethod}."
                );
            }

            $response = $agent->{$this->agentMethod}($testCase->input);

            if (! is_string($response)) {
                throw new RuntimeException(
                    "Agent method {$this->agentMethod} must return a string response."
                );
            }

            $assertion = $this->assert(
                $response,
                $testCase->expect
            );

            if ($assertion['passed']) {
                return EvalResult::pass(
                    $testCase->name,
                    $response,
                    $testCase->tags
                );
            }

            return EvalResult::fail(
                $testCase->name,
                $response,
                $assertion['reason'] ?? 'Assertion failed.',
                $testCase->tags
            );
        } catch (Throwable $e) {
            return EvalResult::fail(
                $testCase->name,
                '',
                'Agent execution failed: '.$e->getMessage(),
                $testCase->tags
            );
        }
    }

    /**
     * Run all assertions against an agent response.
     *
     * @param array{
     *     contains?: string|array<string>,
     *     not_contains?: string|array<string>,
     *     callback?: mixed
     * } $expect
     * @return array{passed: bool, reason?: string}
     */
    public function assert(string $response, array $expect): array
    {
        if (array_key_exists('contains', $expect)) {
            $values = $this->normalizeValues($expect['contains']);

            foreach ($values as $value) {
                if (! str_contains($response, $value)) {
                    return [
                        'passed' => false,
                        'reason' => "Response does not contain expected text: \"{$value}\".",
                    ];
                }
            }
        }

        if (array_key_exists('not_contains', $expect)) {
            $values = $this->normalizeValues($expect['not_contains']);

            foreach ($values as $value) {
                if (str_contains($response, $value)) {
                    return [
                        'passed' => false,
                        'reason' => "Response contains forbidden text: \"{$value}\".",
                    ];
                }
            }
        }

        if (array_key_exists('callback', $expect)) {
            $callback = $expect['callback'];

            if (! $callback instanceof Closure) {
                return [
                    'passed' => false,
                    'reason' => 'Callback assertion must be a Closure.',
                ];
            }

            try {
                $passed = $callback($response);
            } catch (Throwable $e) {
                return [
                    'passed' => false,
                    'reason' => 'Callback assertion threw an exception: '.$e->getMessage(),
                ];
            }

            if ($passed !== true) {
                return [
                    'passed' => false,
                    'reason' => 'Callback assertion returned false.',
                ];
            }
        }

        return [
            'passed' => true,
        ];
    }

    /**
     * @param  string|list<string>  $value
     * @return list<string>
     */
    private function normalizeValues(string|array $value): array
    {
        return is_array($value) ? $value : [$value];
    }

    /**
     * @param  list<string>  $tags
     */
    private function matchesTags(TestCase $testCase, array $tags): bool
    {
        if ($tags === []) {
            return true;
        }

        foreach ($tags as $tag) {
            if ($testCase->hasTag($tag)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load TestCase objects from the configured test directory.
     *
     * Each PHP file may return:
     * - one TestCase instance
     * - an array of TestCase instances
     *
     * @return array<TestCase>
     */
    private function loadTestCases(): array
    {
        if (! is_dir($this->testPath)) {
            throw new RuntimeException(
                "Agent eval test directory does not exist: {$this->testPath}"
            );
        }

        $files = glob(
            rtrim($this->testPath, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .'*.php'
        );

        if ($files === false) {
            throw new RuntimeException(
                "Unable to read agent eval test directory: {$this->testPath}"
            );
        }

        sort($files);

        $testCases = [];

        foreach ($files as $file) {
            $loaded = require $file;

            if ($loaded instanceof TestCase) {
                $testCases[] = $loaded;

                continue;
            }

            if (is_array($loaded)) {
                foreach ($loaded as $testCase) {
                    if (! $testCase instanceof TestCase) {
                        throw new RuntimeException(
                            "File {$file} must return only TestCase instances."
                        );
                    }

                    $testCases[] = $testCase;
                }

                continue;
            }

            throw new RuntimeException(
                "File {$file} must return a TestCase or an array of TestCase instances."
            );
        }

        return $testCases;
    }
}
