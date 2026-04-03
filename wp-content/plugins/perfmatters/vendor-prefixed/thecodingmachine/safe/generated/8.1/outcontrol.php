<?php

namespace Perfmatters\Vendor\Safe;

use Perfmatters\Vendor\Safe\Exceptions\OutcontrolException;
/**
 * @throws OutcontrolException
 *
 * @internal
 */
function ob_clean() : void
{
    \error_clear_last();
    $safeResult = \ob_clean();
    if ($safeResult === \false) {
        throw OutcontrolException::createFromPhpError();
    }
}
/**
 * @throws OutcontrolException
 *
 * @internal
 */
function ob_end_clean() : void
{
    \error_clear_last();
    $safeResult = \ob_end_clean();
    if ($safeResult === \false) {
        throw OutcontrolException::createFromPhpError();
    }
}
/**
 * @throws OutcontrolException
 *
 * @internal
 */
function ob_end_flush() : void
{
    \error_clear_last();
    $safeResult = \ob_end_flush();
    if ($safeResult === \false) {
        throw OutcontrolException::createFromPhpError();
    }
}
/**
 * @throws OutcontrolException
 *
 * @internal
 */
function ob_flush() : void
{
    \error_clear_last();
    $safeResult = \ob_flush();
    if ($safeResult === \false) {
        throw OutcontrolException::createFromPhpError();
    }
}
/**
 * @return string
 * @throws OutcontrolException
 *
 * @internal
 */
function ob_get_clean() : string
{
    \error_clear_last();
    $safeResult = \ob_get_clean();
    if ($safeResult === \false) {
        throw OutcontrolException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param array|callable|null|string $callback
 * @param int $chunk_size
 * @param int $flags
 * @throws OutcontrolException
 *
 * @internal
 */
function ob_start($callback = null, int $chunk_size = 0, int $flags = \PHP_OUTPUT_HANDLER_STDFLAGS) : void
{
    \error_clear_last();
    if ($flags !== \PHP_OUTPUT_HANDLER_STDFLAGS) {
        $safeResult = \ob_start($callback, $chunk_size, $flags);
    } elseif ($chunk_size !== 0) {
        $safeResult = \ob_start($callback, $chunk_size);
    } elseif ($callback !== null) {
        $safeResult = \ob_start($callback);
    } else {
        $safeResult = \ob_start();
    }
    if ($safeResult === \false) {
        throw OutcontrolException::createFromPhpError();
    }
}
/**
 * @param string $name
 * @param string $value
 * @throws OutcontrolException
 *
 * @internal
 */
function output_add_rewrite_var(string $name, string $value) : void
{
    \error_clear_last();
    $safeResult = \output_add_rewrite_var($name, $value);
    if ($safeResult === \false) {
        throw OutcontrolException::createFromPhpError();
    }
}
/**
 * @throws OutcontrolException
 *
 * @internal
 */
function output_reset_rewrite_vars() : void
{
    \error_clear_last();
    $safeResult = \output_reset_rewrite_vars();
    if ($safeResult === \false) {
        throw OutcontrolException::createFromPhpError();
    }
}
