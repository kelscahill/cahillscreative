<?php

declare (strict_types=1);
namespace Perfmatters\Vendor\Sabberworm\CSS\Parsing;

/**
 * Thrown if the CSS parser encounters end of file it did not expect.
 *
 * Extends `UnexpectedTokenException` in order to preserve backwards compatibility.
 * @internal
 */
final class UnexpectedEOFException extends UnexpectedTokenException
{
}
