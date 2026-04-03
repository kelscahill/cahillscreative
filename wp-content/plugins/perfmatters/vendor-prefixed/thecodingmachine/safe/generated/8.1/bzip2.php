<?php

namespace Perfmatters\Vendor\Safe;

use Perfmatters\Vendor\Safe\Exceptions\Bzip2Exception;
/**
 * @param resource $bz
 * @throws Bzip2Exception
 *
 * @internal
 */
function bzclose($bz) : void
{
    \error_clear_last();
    $safeResult = \bzclose($bz);
    if ($safeResult === \false) {
        throw Bzip2Exception::createFromPhpError();
    }
}
/**
 * @param resource $bz
 * @throws Bzip2Exception
 *
 * @internal
 */
function bzflush($bz) : void
{
    \error_clear_last();
    $safeResult = \bzflush($bz);
    if ($safeResult === \false) {
        throw Bzip2Exception::createFromPhpError();
    }
}
/**
 * @param resource|string $file
 * @param string $mode
 * @return resource
 * @throws Bzip2Exception
 *
 * @internal
 */
function bzopen($file, string $mode)
{
    \error_clear_last();
    $safeResult = \bzopen($file, $mode);
    if ($safeResult === \false) {
        throw Bzip2Exception::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param resource $bz
 * @param int $length
 * @return string
 * @throws Bzip2Exception
 *
 * @internal
 */
function bzread($bz, int $length = 1024) : string
{
    \error_clear_last();
    $safeResult = \bzread($bz, $length);
    if ($safeResult === \false) {
        throw Bzip2Exception::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param resource $bz
 * @param string $data
 * @param int|null $length
 * @return int
 * @throws Bzip2Exception
 *
 * @internal
 */
function bzwrite($bz, string $data, ?int $length = null) : int
{
    \error_clear_last();
    if ($length !== null) {
        $safeResult = \bzwrite($bz, $data, $length);
    } else {
        $safeResult = \bzwrite($bz, $data);
    }
    if ($safeResult === \false) {
        throw Bzip2Exception::createFromPhpError();
    }
    return $safeResult;
}
