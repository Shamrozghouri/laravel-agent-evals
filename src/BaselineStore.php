<?php

namespace Ali\LaravelAgentEvals;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final class BaselineStore
{
    public function __construct(
        private readonly string $path,
        private readonly Filesystem $files = new Filesystem
    ) {}

    /**
     * Load the previous baseline.
     *
     * @return array<EvalResult>
     */
    public function load(): array
    {
        if (! $this->files->exists($this->path)) {
            return [];
        }

        $contents = $this->files->get($this->path);

        if (trim($contents) === '') {
            return [];
        }

        try {
            $data = json_decode(
                $contents,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            throw new RuntimeException(
                "Unable to read agent eval baseline at {$this->path}: {$e->getMessage()}",
                previous: $e
            );
        }

        if (! is_array($data)) {
            throw new RuntimeException(
                "Invalid agent eval baseline format at {$this->path}."
            );
        }

        $results = [];

        foreach ($data as $entry) {
            if (! is_array($entry) || ! isset($entry['name'])) {
                continue;
            }

            $results[] = EvalResult::fromArray($entry);
        }

        return $results;
    }

    /**
     * Save the current results as the new baseline.
     *
     * @param  array<EvalResult>  $results
     */
    public function save(array $results): void
    {
        $directory = dirname($this->path);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory(
                $directory,
                0755,
                true
            );
        }

        $data = array_map(
            static fn (EvalResult $result): array => $result->toArray(),
            $results
        );

        try {
            $json = json_encode(
                array_values($data),
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            throw new RuntimeException(
                "Unable to encode agent eval baseline: {$e->getMessage()}",
                previous: $e
            );
        }

        $this->files->put(
            $this->path,
            $json.PHP_EOL
        );
    }

    /**
     * Get the baseline result for a specific test case.
     */
    public function get(string $name): ?EvalResult
    {
        foreach ($this->load() as $result) {
            if ($result->name === $name) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Check whether a current failed result is a regression.
     */
    public function isRegression(
        EvalResult $current,
        ?EvalResult $baseline
    ): bool {
        return $baseline?->passed === true
            && $current->passed === false;
    }
}
