<?php

namespace Ali\LaravelAgentEvals\Tests\Unit;

use Ali\LaravelAgentEvals\BaselineStore;
use Ali\LaravelAgentEvals\EvalResult;
use PHPUnit\Framework\TestCase;

class BaselineStoreTest extends TestCase
{
    private string $baselinePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baselinePath = __DIR__.'/../Fixtures/test-baseline.json';

        if (file_exists($this->baselinePath)) {
            unlink($this->baselinePath);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->baselinePath)) {
            unlink($this->baselinePath);
        }

        parent::tearDown();
    }

    public function test_baseline_can_be_saved_and_loaded(): void
    {
        $store = new BaselineStore($this->baselinePath);

        $results = [
            EvalResult::pass(
                'greets the user',
                'Hello back!'
            ),
        ];

        $store->save($results);

        $loaded = $store->load();

        $this->assertCount(1, $loaded);
        $this->assertSame('greets the user', $loaded[0]->name);
        $this->assertTrue($loaded[0]->passed);
        $this->assertSame('Hello back!', $loaded[0]->response);
    }
}
