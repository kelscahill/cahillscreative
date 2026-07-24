<?php

namespace WPForms\Vendor\Box\Spout\Writer\ODS\Creator;

use WPForms\Vendor\Box\Spout\Common\Helper\Escaper;
use WPForms\Vendor\Box\Spout\Common\Helper\StringHelper;
use WPForms\Vendor\Box\Spout\Common\Manager\OptionsManagerInterface;
use WPForms\Vendor\Box\Spout\Writer\Common\Creator\InternalEntityFactory;
use WPForms\Vendor\Box\Spout\Writer\Common\Entity\Options;
use WPForms\Vendor\Box\Spout\Writer\Common\Helper\ZipHelper;
use WPForms\Vendor\Box\Spout\Writer\ODS\Helper\FileSystemHelper;
/**
 * Class HelperFactory
 * Factory for helpers needed by the ODS Writer
 */
class HelperFactory extends \WPForms\Vendor\Box\Spout\Common\Creator\HelperFactory
{
    /**
     * @param OptionsManagerInterface $optionsManager
     * @param InternalEntityFactory $entityFactory
     * @return FileSystemHelper
     */
    public function createSpecificFileSystemHelper(OptionsManagerInterface $optionsManager, InternalEntityFactory $entityFactory)
    {
        $tempFolder = $optionsManager->getOption(Options::TEMP_FOLDER);
        $zipHelper = $this->createZipHelper($entityFactory);
        return new FileSystemHelper($tempFolder, $zipHelper);
    }
    /**
     * @param $entityFactory
     * @return ZipHelper
     */
    private function createZipHelper($entityFactory)
    {
        return new ZipHelper($entityFactory);
    }
    /**
     * @return Escaper\ODS
     */
    public function createStringsEscaper()
    {
        return new Escaper\ODS();
    }
    /**
     * @return StringHelper
     */
    public function createStringHelper()
    {
        return new StringHelper();
    }
}
