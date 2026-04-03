<?php

namespace Perfmatters\Vendor\Safe;

use Perfmatters\Vendor\Safe\Exceptions\GettextException;
/**
 * @param string $domain
 * @param null|string $directory
 * @return string
 * @throws GettextException
 *
 * @internal
 */
function bindtextdomain(string $domain, ?string $directory = null) : string
{
    \error_clear_last();
    if ($directory !== null) {
        $safeResult = \bindtextdomain($domain, $directory);
    } else {
        $safeResult = \bindtextdomain($domain);
    }
    if ($safeResult === \false) {
        throw GettextException::createFromPhpError();
    }
    return $safeResult;
}
