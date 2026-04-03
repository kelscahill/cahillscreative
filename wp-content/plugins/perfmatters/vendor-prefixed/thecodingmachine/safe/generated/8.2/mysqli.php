<?php

namespace Perfmatters\Vendor\Safe;

use Perfmatters\Vendor\Safe\Exceptions\MysqliException;
/**
 * @return array|false
 *
 * @internal
 */
function mysqli_get_client_stats()
{
    \error_clear_last();
    $safeResult = \mysqli_get_client_stats();
    return $safeResult;
}
