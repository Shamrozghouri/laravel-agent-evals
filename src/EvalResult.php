<?php

namespace Ali\LaravelAgentEvals;

/**
 * Describes the outcome of running one TestCase against the agent.
 *
 * This is the shape that gets printed in the terminal report, written to
 * the baseline JSON file, and compared against the previous baseline to
 * detect regressions. Matches the baseline entry format defined in the
 * spec (§4):
 *
 *   {
 *     "name": "declines refunds over $100 without approval",
 *     "passed": true,
 *     "response": "Refunds over $100 need manager approval...",
 *     "reason": null
 *   }
 */
final class EvalResult
{
    /**
     * @param  string  $name  Matches the TestCase name this result belongs to.
     * @param  bool  $passed  Whether all assertions in the TestCase passed.
     * @param  string  $response  The raw response returned by the agent.
     * @param  string|null  $reason  Human-readable explanation when $passed is false.
     *                               Null when the test passed.
     * @param  array<string>  $tags  Tags copied from the originating TestCase,
     *                               carried along so filtering/reporting can use them.
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $passed,
        public readonly string $response,
        public readonly ?string $reason = null,
        public readonly array $tags = []
    ) {}

    /**
     * Convenience factory for a passing result.
     *
     * @param  array<string>  $tags
     */
    public static function pass(string $name, string $response, array $tags = []): self
    {
        return new self(
            name: $name,
            passed: true,
            response: $response,
            reason: null,
            tags: $tags
        );
    }

    /**
     * Convenience factory for a failing result.
     *
     * @param  array<string>  $tags
     */
    public static function fail(string $name, string $response, string $reason, array $tags = []): self
    {
        return new self(
            name: $name,
            passed: false,
            response: $response,
            reason: $reason,
            tags: $tags
        );
    }

    /**
     * Convert to the plain array shape used for JSON baseline storage
     * and for terminal/HTML report rendering.
     *
     * @return array{name: string, passed: bool, response: string, reason: string|null, tags: array<string>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'passed' => $this->passed,
            'response' => $this->response,
            'reason' => $this->reason,
            'tags' => $this->tags,
        ];
    }

    /**
     * Rebuild an EvalResult from a decoded baseline JSON entry.
     *
     * @param  array{name: string, passed: bool, response: string, reason?: string|null, tags?: array<string>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            passed: (bool) $data['passed'],
            response: $data['response'],
            reason: $data['reason'] ?? null,
            tags: $data['tags'] ?? []
        );
    }
}
