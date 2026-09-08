<?php

namespace Ali\LaravelAgentEvals\Tests\Unit;

use Ali\LaravelAgentEvals\BaselineStore;
use Ali\LaravelAgentEvals\EvalResult;
use PHPUnit\Framework\TestCase;

class BaselineRegressionTest extends TestCase
{
    public function test_pass_to_fail_is_detected_as_regression(): void
    {
        $store = new BaselineStore(
            __DIR__.'/../Fixtures/test-baseline.json'
        );

        $baseline = EvalResult::pass(
            'refund policy',
            'Refunds are available within 30 days.'
        );

        $current = EvalResult::fail(
            'refund policy',
            'I cannot help with refunds.',
            'Expected response to contain: 30 days.'
        );

        $this->assertTrue(
            $store->isRegression($current, $baseline)
        );
    }

    public function test_pass_to_pass_is_not_a_regression(): void
    {
        $store = new BaselineStore(
            __DIR__.'/../Fixtures/test-baseline.json'
        );

        $baseline = EvalResult::pass(
            'refund policy',
            'Refunds are available within 30 days.'
        );

        $current = EvalResult::pass(
            'refund policy',
            'Refunds are available within 30 days.'
        );

        $this->assertFalse(
            $store->isRegression($current, $baseline)
        );
    }
}
