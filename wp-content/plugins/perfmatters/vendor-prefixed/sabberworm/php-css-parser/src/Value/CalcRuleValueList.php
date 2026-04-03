<?php

declare (strict_types=1);
namespace Perfmatters\Vendor\Sabberworm\CSS\Value;

use Perfmatters\Vendor\Sabberworm\CSS\OutputFormat;
/** @internal */
class CalcRuleValueList extends RuleValueList
{
    /**
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(?int $lineNumber = null)
    {
        parent::__construct(',', $lineNumber);
    }
    public function render(OutputFormat $outputFormat) : string
    {
        return $outputFormat->getFormatter()->implode(' ', $this->components);
    }
    /**
     * @return array<string, bool|int|float|string|array<mixed>|null>
     *
     * @internal
     */
    public function getArrayRepresentation() : array
    {
        throw new \BadMethodCallException('`getArrayRepresentation` is not yet implemented for `' . self::class . '`');
    }
}
