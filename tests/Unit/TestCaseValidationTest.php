<?php

namespace Ali\LaravelAgentEvals\Tests\Unit;

use Ali\LaravelAgentEvals\TestCase as AgentTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TestCaseValidationTest extends TestCase
{
    public function test_assertion_lists_must_contain_only_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $expect = unserialize('a:1:{s:8:"contains";a:2:{i:0;s:5:"hello";i:1;i:1;}}');

        AgentTestCase::make(
            name: 'invalid assertion list',
            input: 'Hello',
            expect: $expect,
        );
    }
}
