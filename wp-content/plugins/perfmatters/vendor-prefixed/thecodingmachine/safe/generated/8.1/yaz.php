<?php

namespace Perfmatters\Vendor\Safe;

use Perfmatters\Vendor\Safe\Exceptions\YazException;
/**
 * @param resource $id
 * @param string $query
 * @param array|null $result
 * @throws YazException
 *
 * @internal
 */
function yaz_ccl_parse($id, string $query, ?array &$result) : void
{
    \error_clear_last();
    $safeResult = \yaz_ccl_parse($id, $query, $result);
    if ($safeResult === \false) {
        throw YazException::createFromPhpError();
    }
}
/**
 * @param resource $id
 * @throws YazException
 *
 * @internal
 */
function yaz_close($id) : void
{
    \error_clear_last();
    $safeResult = \yaz_close($id);
    if ($safeResult === \false) {
        throw YazException::createFromPhpError();
    }
}
/**
 * @param string $zurl
 * @param mixed $options
 * @return mixed
 * @throws YazException
 *
 * @internal
 */
function yaz_connect(string $zurl, $options = null)
{
    \error_clear_last();
    if ($options !== null) {
        $safeResult = \yaz_connect($zurl, $options);
    } else {
        $safeResult = \yaz_connect($zurl);
    }
    if ($safeResult === \false) {
        throw YazException::createFromPhpError();
    }
    return $safeResult;
}
/**
 * @param resource $id
 * @param string $databases
 * @throws YazException
 *
 * @internal
 */
function yaz_database($id, string $databases) : void
{
    \error_clear_last();
    $safeResult = \yaz_database($id, $databases);
    if ($safeResult === \false) {
        throw YazException::createFromPhpError();
    }
}
/**
 * @param resource $id
 * @param string $elementset
 * @throws YazException
 *
 * @internal
 */
function yaz_element($id, string $elementset) : void
{
    \error_clear_last();
    $safeResult = \yaz_element($id, $elementset);
    if ($safeResult === \false) {
        throw YazException::createFromPhpError();
    }
}
/**
 * @param resource $id
 * @throws YazException
 *
 * @internal
 */
function yaz_present($id) : void
{
    \error_clear_last();
    $safeResult = \yaz_present($id);
    if ($safeResult === \false) {
        throw YazException::createFromPhpError();
    }
}
/**
 * @param resource $id
 * @param string $type
 * @param string $query
 * @throws YazException
 *
 * @internal
 */
function yaz_search($id, string $type, string $query) : void
{
    \error_clear_last();
    $safeResult = \yaz_search($id, $type, $query);
    if ($safeResult === \false) {
        throw YazException::createFromPhpError();
    }
}
/**
 * @param array $options
 * @return mixed
 * @throws YazException
 *
 * @internal
 */
function yaz_wait(?array &$options = null)
{
    \error_clear_last();
    $safeResult = \yaz_wait($options);
    if ($safeResult === \false) {
        throw YazException::createFromPhpError();
    }
    return $safeResult;
}
