<?php

namespace Perfmatters\Vendor\Safe;

use Perfmatters\Vendor\Safe\Exceptions\SolrException;
/**
 * @return string
 * @throws SolrException
 *
 * @internal
 */
function solr_get_version() : string
{
    \error_clear_last();
    $safeResult = \solr_get_version();
    if ($safeResult === \false) {
        throw SolrException::createFromPhpError();
    }
    return $safeResult;
}
