<?php

use Ali\LaravelAgentEvals\TestCase;

return TestCase::make(
    name: 'response satisfies custom rule',
    input: 'What is 2 + 2?',
    expect: [
        'callback' => function (string $response): bool {
            return str_contains($response, '4')
                && strlen($response) < 100;
        },
    ],
);
