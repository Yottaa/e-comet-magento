<?php

class Yottaa_Yottaa_Model_Resource_Mysql4_Core_Url_Rewrite_Collection
    extends Mage_Core_Model_Mysql4_Url_Rewrite_Collection
{
    /**
     * Filter collection by category id
     *
     * @param int $categoryId
     * @return Yottaa_Yottaa_Model_Resource_Mysql4_Core_Url_Rewrite_Collection
     */
    public function filterAllByCategoryId($categoryId)
    {
        $this->getSelect()
            ->where('id_path = ?', "category/{$categoryId}");
        return $this;
    }
}
