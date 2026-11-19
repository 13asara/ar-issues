<?php

return [
    'node' => env('MIND_COMPILER_NODE', 'node'),
    'token' => env('MIND_COMPILE_TOKEN', ''),
    'timeout' => (int) env('MIND_COMPILE_TIMEOUT', 900),
];
