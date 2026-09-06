<?php

/*
|--------------------------------------------------------------------------
| Anthropic list prices used to estimate what each Claude call costs
|--------------------------------------------------------------------------
| USD per million tokens. Cache reads/writes are priced as a multiple of the
| input price. Unknown models fall back to `default` and are flagged so the
| dashboard can say the figure is approximate. The Anthropic Console is the
| authoritative bill; these numbers exist so the app can warn early.
*/

return [
    'currency' => 'USD',

    'default' => ['input' => 5, 'output' => 25],

    'models' => [
        'claude-fable-5-1' => ['input' => 10, 'output' => 50],
        'claude-fable-5' => ['input' => 10, 'output' => 50],
        'claude-opus-5' => ['input' => 5, 'output' => 25],
        'claude-opus-4-8' => ['input' => 5, 'output' => 25],
        'claude-opus-4-7' => ['input' => 5, 'output' => 25],
        'claude-opus-4-6' => ['input' => 5, 'output' => 25],
        'claude-opus-4-5' => ['input' => 5, 'output' => 25],
        'claude-sonnet-5' => ['input' => 2, 'output' => 10],
        'claude-sonnet-4-6' => ['input' => 3, 'output' => 15],
        'claude-sonnet-4-5' => ['input' => 3, 'output' => 15],
        'claude-haiku-4-5' => ['input' => 1, 'output' => 5],
    ],

    'cache_read_multiplier' => 0.1,
    'cache_write_multiplier' => 1.25,
];
