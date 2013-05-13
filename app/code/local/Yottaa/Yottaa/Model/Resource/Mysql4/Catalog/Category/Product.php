<?php

class Yottaa_Yottaa_Model_Resource_Mysql4_Catalog_Category_Product
    extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Initialize resource model and define main table
     */
    protected function _construct()
    {
        $this->_init('catalog/category_product', 'category_id');
    }
}
