<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
    // All prefixed classes will live under this root namespace.
    // Example: Sabberworm\CSS\Parser -> Perfmatters\Vendor\Sabberworm\CSS\Parser
    'prefix' => 'Perfmatters\\Vendor',

    // Scope third-party parser/runtime dependencies from Composer's vendor dir.
    'finders' => [
        Finder::create()
            ->files()
            ->in(__DIR__ . '/vendor/sabberworm/php-css-parser/src')
            ->name('*.php'),
        Finder::create()
            ->files()
            ->in(__DIR__ . '/vendor/thecodingmachine/safe')
            ->name('*.php'),
    ],
];

