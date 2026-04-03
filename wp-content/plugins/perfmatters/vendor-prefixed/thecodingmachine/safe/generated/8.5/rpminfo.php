<?php

namespace Perfmatters\Vendor\Safe;

use Perfmatters\Vendor\Safe\Exceptions\RpminfoException;
/**
 * @param int $tag
 * @throws RpminfoException
 *
 * @internal
 */
function rpmaddtag(int $tag) : void
{
    \error_clear_last();
    $safeResult = \rpmaddtag($tag);
    if ($safeResult === \false) {
        throw RpminfoException::createFromPhpError();
    }
}
/**
 * @param string $text
 * @throws RpminfoException
 *
 * @internal
 */
function rpmdefine(string $text) : void
{
    \error_clear_last();
    $safeResult = \Perfmatters\Vendor\rpmdefine($text);
    if ($safeResult === \false) {
        throw RpminfoException::createFromPhpError();
    }
}
