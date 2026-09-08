<?php

namespace Ali\LaravelAgentEvals;

use Closure;
use InvalidArgumentException;

/**
 * Describes one expected behavior of an AI agent: an input prompt plus
 * the assertions its response must satisfy.
 *
 * A test case never runs itself — EvalRunner is responsible for sending
 * `input` to the configured agent and checking the response against
 * `expect`. This class is purely a declarative description.
 */
final class TestCase
{
    /**
     * @var string Human-readable name for this test case.
     */
    public readonly string $name;

    /**
     * @var string The prompt sent to the agent.
     */
    public readonly string $input;

    /**
     * @var array{
     *     contains?: string|array<string>,
     *     not_contains?: string|array<string>,
     *     callback?: Closure
     * } The assertions the agent's response must satisfy.
     */
    public readonly array $expect;

    /**
     * @var array<string> Optional tags for filtering (e.g. --tags=refunds).
     */
    public readonly array $tags;

    /**
     * @param  string  $name  Human-readable name for this test case.
     * @param  string  $input  The prompt to send to the agent.
     * @param array{
     *     contains?: string|array<string>,
     *     not_contains?: string|array<string>,
     *     callback?: Closure
     * } $expect At least one assertion key is required.
     * @param  array<string>  $tags  Optional tags for filtering.
     */
    public function __construct(
        string $name,
        string $input,
        array $expect,
        array $tags = []
    ) {
        $this->guardExpect($expect);

        $this->name = $name;
        $this->input = $input;
        $this->expect = $expect;
        $this->tags = $tags;
    }

    /**
     * Convenience factory so test case files read cleanly:
     *
     *   return TestCase::make(
     *       name: 'declines refunds over $100 without approval',
     *       input: 'Can you refund my $150 order right now?',
     *       expect: ['contains' => 'manager approval'],
     *   );
     *
     * @param array{
     *     contains?: string|list<string>,
     *     not_contains?: string|list<string>,
     *     callback?: Closure
     * } $expect
     * @param  array<string>  $tags
     */
    public static function make(
        string $name,
        string $input,
        array $expect,
        array $tags = []
    ): self {
        return new self($name, $input, $expect, $tags);
    }

    /**
     * Whether this test case carries a given tag.
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }

    /**
     * Ensure at least one recognized assertion key was provided, and that
     * each provided key has a valid shape. Fails fast at definition time
     * rather than producing a confusing runtime error mid-run.
     */
    /**
     * @param  array<string, mixed>  $expect
     */
    private function guardExpect(array $expect): void
    {
        $validKeys = ['contains', 'not_contains', 'callback'];

        if (empty($expect)) {
            throw new InvalidArgumentException(
                'TestCase "expect" must define at least one assertion: '
                .implode(', ', $validKeys).'.'
            );
        }

        foreach (array_keys($expect) as $key) {
            if (! in_array($key, $validKeys, true)) {
                throw new InvalidArgumentException(
                    "Unknown assertion key \"{$key}\" in TestCase expect. "
                    .'Valid keys are: '.implode(', ', $validKeys).'.'
                );
            }
        }

        if (isset($expect['callback']) && ! $expect['callback'] instanceof Closure) {
            throw new InvalidArgumentException(
                'TestCase "expect.callback" must be a Closure accepting '
                .'the agent response string and returning a bool.'
            );
        }

        foreach (['contains', 'not_contains'] as $key) {
            if (! isset($expect[$key])) {
                continue;
            }

            $value = $expect[$key];

            if (! is_string($value)
                && (! is_array($value)
                    || ! array_is_list($value)
                    || array_filter($value, static fn (mixed $item): bool => ! is_string($item)) !== [])
            ) {
                throw new InvalidArgumentException(
                    "TestCase \"expect.{$key}\" must be a string or a list of strings."
                );
            }
        }
    }
}
