<?php

namespace WPForms\Vendor\Box\Spout\Reader\Common\Creator;

use WPForms\Vendor\Box\Spout\Common\Entity\Cell;
use WPForms\Vendor\Box\Spout\Common\Entity\Row;
/**
 * Interface EntityFactoryInterface
 */
interface InternalEntityFactoryInterface
{
    /**
     * @param Cell[] $cells
     * @return Row
     */
    public function createRow(array $cells = []);
    /**
     * @param mixed $cellValue
     * @return Cell
     */
    public function createCell($cellValue);
}
