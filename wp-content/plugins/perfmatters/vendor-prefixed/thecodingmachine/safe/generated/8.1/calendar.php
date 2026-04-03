<?php

namespace Perfmatters\Vendor\Safe;

use Perfmatters\Vendor\Safe\Exceptions\CalendarException;
/**
 * @param int|null $timestamp
 * @return int
 * @throws CalendarException
 *
 * @internal
 */
function unixtojd(?int $timestamp = null) : int
{
    \error_clear_last();
    if ($timestamp !== null) {
        $safeResult = \unixtojd($timestamp);
    } else {
        $safeResult = \unixtojd();
    }
    if ($safeResult === \false) {
        throw CalendarException::createFromPhpError();
    }
    return $safeResult;
}
