<?php

namespace Ali\LaravelAgentEvals\Tests\Unit;

use Ali\LaravelAgentEvals\BaselineStore;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BaselineStoreFailureTest extends TestCase
{
    private string $baselinePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baselinePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'agent-evals-invalid-baseline.json';
        file_put_contents($this->baselinePath, '{invalid json');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->baselinePath)) {
            unlink($this->baselinePath);
        }

        parent::tearDown();
    }

    public function test_malformed_baseline_throws_a_clear_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to read agent eval baseline');

        (new BaselineStore($this->baselinePath))->load();
    }
}
