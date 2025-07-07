<?php

namespace WPForms\Vendor\Box\Spout\Writer\Common\Creator;

use WPForms\Vendor\Box\Spout\Common\Manager\OptionsManagerInterface;
use WPForms\Vendor\Box\Spout\Writer\Common\Manager\SheetManager;
use WPForms\Vendor\Box\Spout\Writer\Common\Manager\WorkbookManagerInterface;
/**
 * Interface ManagerFactoryInterface
 */
interface ManagerFactoryInterface
{
    /**
     * @param OptionsManagerInterface $optionsManager
     * @return WorkbookManagerInterface
     */
    public function createWorkbookManager(OptionsManagerInterface $optionsManager);
    /**
     * @return SheetManager
     */
    public function createSheetManager();
}
