<?php

namespace WPForms\Vendor\Box\Spout\Writer\CSV\Manager;

use WPForms\Vendor\Box\Spout\Common\Manager\OptionsManagerAbstract;
use WPForms\Vendor\Box\Spout\Writer\Common\Entity\Options;
/**
 * Class OptionsManager
 * CSV Writer options manager
 */
class OptionsManager extends OptionsManagerAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedOptions()
    {
        return [Options::FIELD_DELIMITER, Options::FIELD_ENCLOSURE, Options::SHOULD_ADD_BOM];
    }
    /**
     * {@inheritdoc}
     */
    protected function setDefaultOptions()
    {
        $this->setOption(Options::FIELD_DELIMITER, ',');
        $this->setOption(Options::FIELD_ENCLOSURE, '"');
        $this->setOption(Options::SHOULD_ADD_BOM, \true);
    }
}
