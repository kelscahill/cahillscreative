<?php

namespace Perfmatters\Vendor\Safe;

use Perfmatters\Vendor\Safe\Exceptions\StringsException;
/**
 * @param string $string
 * @return string
 * @throws StringsException
 *
 * @internal
 */
function convert_uudecode(string $string) : string
{
    \error_clear_last();
    $safeResult = \convert_uudecode($string);
    if ($safeResult === \false) {
        throw StringsException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param string $string
 * @return string
 * @throws StringsException
 *
 * @internal
 */
function hex2bin(string $string) : string
{
    \error_clear_last();
    $safeResult = \hex2bin($string);
    if ($safeResult === \false) {
        throw StringsException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param string $filename
 * @param bool $binary
 * @return non-falsy-string&lowercase-string
 * @throws StringsException
 *
 * @internal
 */
function md5_file(string $filename, bool $binary = \false) : string
{
    \error_clear_last();
    $safeResult = \md5_file($filename, $binary);
    if ($safeResult === \false) {
        throw StringsException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param string $filename
 * @param bool $binary
 * @return non-falsy-string&lowercase-string
 * @throws StringsException
 *
 * @internal
 */
function sha1_file(string $filename, bool $binary = \false) : string
{
    \error_clear_last();
    $safeResult = \sha1_file($filename, $binary);
    if ($safeResult === \false) {
        throw StringsException::createFromPhpError();
    }
    return $safeResult;
}
