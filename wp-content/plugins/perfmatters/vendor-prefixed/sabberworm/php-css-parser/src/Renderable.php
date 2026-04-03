<?php

declare (strict_types=1);
namespace Perfmatters\Vendor\Sabberworm\CSS;

/** @internal */
interface Renderable
{
    public function render(OutputFormat $outputFormat) : string;
    /**
     * @return array<string, bool|int|float|string|array<mixed>|null>
     *
     * @internal
     */
    public function getArrayRepresentation() : array;
}
