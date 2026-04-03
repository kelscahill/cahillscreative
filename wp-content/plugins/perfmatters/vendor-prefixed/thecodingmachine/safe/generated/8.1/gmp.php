<?php

namespace Perfmatters\Vendor\Safe;

use Perfmatters\Vendor\Safe\Exceptions\GmpException;
/**
 * @param \GMP|int|string $seed
 * @throws GmpException
 *
 * @internal
 */
function gmp_random_seed($seed) : void
{
    \error_clear_last();
    $safeResult = \gmp_random_seed($seed);
    if ($safeResult === \false) {
        throw GmpException::createFromPhpError();
    }
}
