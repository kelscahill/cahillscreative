<?php

declare (strict_types=1);
namespace Perfmatters\Vendor\Sabberworm\CSS\Rule;

use Perfmatters\Vendor\Sabberworm\CSS\Property\Declaration;
use function Perfmatters\Vendor\Safe\class_alias;
if (!\class_exists(Rule::class, \false) && !\interface_exists(Rule::class, \false)) {
    /**
     * @deprecated in v9.2, will be removed in v10.0.  Use `Property\Declaration` instead, which is a direct
     *             replacement.
     */
    class_alias(Declaration::class, Rule::class);
}
