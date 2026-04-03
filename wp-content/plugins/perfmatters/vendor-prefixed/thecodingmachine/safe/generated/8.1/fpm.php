<?php

namespace Perfmatters\Vendor\Safe;

use Perfmatters\Vendor\Safe\Exceptions\FpmException;
/**
 * @throws FpmException
 *
 * @internal
 */
function fastcgi_finish_request() : void
{
    \error_clear_last();
    $safeResult = \fastcgi_finish_request();
    if ($safeResult === \false) {
        throw FpmException::createFromPhpError();
    }
}
