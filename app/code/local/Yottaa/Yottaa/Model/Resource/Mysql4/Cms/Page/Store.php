<?php

class Yottaa_Yottaa_Model_Resource_Mysql4_Cms_Page_Store
    extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Initialize resource model and define main table
     */
    protected function _construct()
    {
        $this->_init('cms/page_store', 'store_id');
    }
}
