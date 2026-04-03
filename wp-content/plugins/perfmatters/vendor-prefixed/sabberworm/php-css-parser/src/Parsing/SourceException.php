<?php

declare (strict_types=1);
namespace Perfmatters\Vendor\Sabberworm\CSS\Parsing;

use Perfmatters\Vendor\Sabberworm\CSS\Position\Position;
use Perfmatters\Vendor\Sabberworm\CSS\Position\Positionable;
/** @internal */
class SourceException extends \Exception implements Positionable
{
    use Position;
    /**
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(string $message, ?int $lineNumber = null)
    {
        $this->setPosition($lineNumber);
        if ($lineNumber !== null) {
            $message .= " [line no: {$lineNumber}]";
        }
        parent::__construct($message);
    }
}
