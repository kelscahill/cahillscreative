<?php

namespace Perfmatters\Vendor\Safe;

use Perfmatters\Vendor\Safe\Exceptions\ShmopException;
/**
 * @param \Shmop $shmop
 * @throws ShmopException
 *
 * @internal
 */
function shmop_delete(\Shmop $shmop) : void
{
    \error_clear_last();
    $safeResult = \shmop_delete($shmop);
    if ($safeResult === \false) {
        throw ShmopException::createFromPhpError();
    }
}
/**
 * @param \Shmop $shmop
 * @param int $offset
 * @param int $size
 * @return string
 *
 * @internal
 */
function shmop_read(\Shmop $shmop, int $offset, int $size) : string
{
    \error_clear_last();
    $safeResult = \shmop_read($shmop, $offset, $size);
    return $safeResult;
}
