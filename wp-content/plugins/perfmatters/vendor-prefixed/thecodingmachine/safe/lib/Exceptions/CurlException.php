<?php

namespace Perfmatters\Vendor\Safe\Exceptions;

/** @internal */
class CurlException extends \Exception implements SafeExceptionInterface
{
    /**
     * @param \CurlHandle $ch
     */
    public static function createFromPhpError($ch = null) : self
    {
        return new self($ch ? \curl_error($ch) : '', $ch ? \curl_errno($ch) : 0);
    }
}
