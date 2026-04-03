<?php

namespace Perfmatters\Vendor\Safe;

use Perfmatters\Vendor\Safe\Exceptions\VarException;
/**
 * @param mixed $var
 * @param string $type
 * @throws VarException
 *
 * @internal
 */
function settype(&$var, string $type) : void
{
    \error_clear_last();
    $safeResult = \settype($var, $type);
    if ($safeResult === \false) {
        throw VarException::createFromPhpError();
    }
}
