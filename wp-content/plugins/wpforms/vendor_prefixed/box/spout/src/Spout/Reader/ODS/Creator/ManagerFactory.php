<?php

namespace WPForms\Vendor\Box\Spout\Reader\ODS\Creator;

use WPForms\Vendor\Box\Spout\Reader\Common\Manager\RowManager;
/**
 * Class ManagerFactory
 * Factory to create managers
 */
class ManagerFactory
{
    /**
     * @param InternalEntityFactory $entityFactory Factory to create entities
     * @return RowManager
     */
    public function createRowManager($entityFactory)
    {
        return new RowManager($entityFactory);
    }
}
