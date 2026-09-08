<?php

use Ali\LaravelAgentEvals\TestCase;

return TestCase::make(
    name: 'answers billing questions',
    input: 'How can I update my card?',
    expect: ['contains' => 'Hello back!'],
    tags: ['billing'],
);
