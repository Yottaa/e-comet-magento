<?php

class Yottaa_Yottaa_Model_Resource_Mysql4_Catalog_Product_Relation_Collection
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Initialize resource model and define main table
     */
    protected function _construct()
    {
        $this->_init('yottaa_yottaa/catalog_product_relation', 'catalog/product_relation');
    }

    /**
     * Filters collection by child product id
     *
     * @param int $childId
     * @return Yottaa_Yottaa_Model_Resource_Mysql4_Catalog_Product_Relation_Collection
     */
    public function filterByChildId($childId)
    {
        $this->getSelect()
            ->where('child_id=?', $childId);
        return $this;
    }
}
