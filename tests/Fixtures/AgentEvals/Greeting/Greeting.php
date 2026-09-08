<?php

use Ali\LaravelAgentEvals\TestCase;

return TestCase::make(
    name: 'greets the user',
    input: 'Hello',
    expect: [
        'contains' => 'Hello back!',
    ],
);
