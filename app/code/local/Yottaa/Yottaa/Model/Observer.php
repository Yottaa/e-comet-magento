<?php

class Yottaa_Yottaa_Model_Observer
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Asks the registered Yottaa optimizer to flush caches.
     *
     * @return mixed
     */
    public function flush_yottaa_cache()
    {
        $helper = Mage::helper('yottaa_yottaa');
        $json_output = $helper->flush();
        if (isset($json_output["error"])) {
            $helper->log('Failed to flush Yottaa cache.');
            $helper->log($json_output["error"]);
        } else {
            $helper->log('Yottaa cache has been successfully flushed!');
        }
        return $json_output;
    }

    /**
     * Automatically flushes cache for an updated node or a node whose comments have been updated, created or deleted.
     *
     * @param $product
     * @return void
     */
    private function auto_flush_yottaa_cache($path_configs)
    {
        $helper = Mage::helper('yottaa_yottaa');
        if ($helper->getAutoClearCacheParameter() == 1) {
            $helper->log('Purge Yottaa cache with configs:');
            $helper->log(json_encode($path_configs));
            $json_output = $helper->flushPaths($path_configs);
            if (isset($json_output["error"])) {
                $helper->log('Failed to pruge Yottaa cache.');
                $helper->log($json_output["error"]);
            } else {
                $helper->log('Yottaa caches have been successfully purged.');
            }
            return $json_output;
        }
    }

    /**
     * @param $id
     * @param array $paths
     * @return array
     */
    private function calculateProductPurgePaths($id, $paths = array())
    {
        $helper = Mage::helper('yottaa_yottaa');
        $collection = Mage::getResourceModel('yottaa_yottaa/core_url_rewrite_collection')
                ->filterAllByProductId($id);
        foreach ($collection as $urlRewriteRule) {
            $urlRegexp = '/' . $urlRewriteRule->getRequestPath();
            if (!in_array($urlRegexp, $paths)) {
                array_push($paths, $urlRegexp);
                $helper->log($urlRegexp);
            }
        }
        return $paths;
    }

    /**
     * @param $id
     * @param array $paths
     * @return array
     */
    private function calculateCategoryPurgePaths($id, $paths = array())
    {
        $helper = Mage::helper('yottaa_yottaa');
        $collection = Mage::getResourceModel('yottaa_yottaa/core_url_rewrite_collection')
                ->filterAllByCategoryId($id);
        foreach ($collection as $urlRewriteRule) {
            $urlRegexp = '/' . $urlRewriteRule->getRequestPath();
            if (!in_array($urlRegexp, $paths)) {
                array_push($paths, $urlRegexp);
                $helper->log($urlRegexp);
            }
        }
        return $paths;
    }

    /**
     * @param $id
     * @param array $paths
     * @return array
     */
    private function calculateCmsPurgePaths($page, $paths = array())
    {
        $helper = Mage::helper('yottaa_yottaa');

        $storeIds = Mage::getResourceModel('yottaa_yottaa/cms_page_store_collection')
                ->addPageFilter($page->getId())
                ->getAllIds();

        if (count($storeIds) && current($storeIds) == 0) {
            $storeIds = Mage::getResourceModel('core/store_collection')
                    ->setWithoutDefaultFilter()
                    ->getAllIds();
        }

        foreach ($storeIds as $storeId) {
            $path = '';
            $url = Mage::app()->getStore($storeId)
                    ->getUrl(null, array('_direct' => $page->getIdentifier()));
            extract(parse_url($url));
            $path = rtrim($path, '/');
            $urlRegexp = '^' . $path . '/{0,1}$';
            if (!in_array($urlRegexp, $paths)) {
                array_push($paths, $urlRegexp);
                $helper->log($urlRegexp);
            }
            // Purge if current page is a home page
            $homePageIdentifier
                    = Mage::getStoreConfig('web/default/cms_home_page', $storeId);
            if ($page->getIdentifier() == $homePageIdentifier) {
                $url = Mage::app()->getStore($storeId)
                        ->getUrl();
                extract(parse_url($url));
                $path = rtrim($path, '/');
                $urlRegexp = '^' . $path . '/{0,1}$';
                if (!in_array($urlRegexp, $paths)) {
                    array_push($paths, $urlRegexp);
                    $helper->log($urlRegexp);
                }
                $urlRegexp = '^/{0,1}$';
                if (!in_array($urlRegexp, $paths)) {
                    array_push($paths, $urlRegexp);
                    $helper->log($urlRegexp);
                }
            }
        }

        return $paths;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Observer
     */
    public function catalog_product_save_after($observer)
    {
        $helper = Mage::helper('yottaa_yottaa');
        $product = $observer->getEvent()->getProduct();
        $id = $product->getId();
        $helper->log("Product save event captured with id " . $id . ".");

        $paths = $this->calculateProductPurgePaths($id);
        // Purge parent product
        $purgeParentProducts = $helper->getPurgeParentProductsParameter();
        $purgeCategories = $helper->getPurgeProductCategoriesParameter();
        if ($purgeParentProducts == 1) {
            // purge parent products
            $productRelationCollection = Mage::getResourceModel('yottaa_yottaa/catalog_product_relation_collection')
                    ->filterByChildId($id);
            foreach ($productRelationCollection as $productRelation) {
                $paths = $this->calculateProductPurgePaths($productRelation->getParentId(), $paths);
            }
            // purge categories of parent products
            if ($purgeCategories == 1) {
                $categoryProductCollection = Mage::getResourceModel('yottaa_yottaa/catalog_category_product_collection')
                        ->filterAllByProductIds($productRelationCollection->getAllIds());
                foreach ($categoryProductCollection as $categoryProduct) {
                    $paths = $this->calculateCategoryPurgePaths($categoryProduct->getCategoryId(), $paths);
                }
            }
        }
        // purge categories of this product
        if ($purgeCategories == 1) {
            foreach ($product->getCategoryCollection() as $category) {
                $paths = $this->calculateCategoryPurgePaths($category->getId(), $paths);
            }
        }
        // Prepare purge path configs
        $path_configs = array();
        foreach ($paths as $path) {
            array_push($path_configs, array("condition" => $path, "name" => "URI", "operator" => "REGEX"));
        }
        $helper->flushPaths($path_configs);

        return $this;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Observer
     */
    public function catalog_category_save_after($observer)
    {
        $helper = Mage::helper('yottaa_yottaa');
        $id = $observer->getEvent()->getCategory()->getId();

        Mage::helper('yottaa_yottaa')->log("Category save event captured with id " . $id . ".");

        $paths = $this->calculateCategoryPurgePaths($id);
        // Prepare purge path configs
        $path_configs = array();
        foreach ($paths as $path) {
            array_push($path_configs, array("condition" => $path, "name" => "URI", "operator" => "REGEX"));
        }
        $helper->flushPaths($path_configs);

        return $this;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Observer
     */
    public function cms_page_save_after($observer)
    {
        $helper = Mage::helper('yottaa_yottaa');
        $page = $observer->getEvent()->getObject();
        $id = $page->getId();

        Mage::helper('yottaa_yottaa')->log("Cms page save event captured with id " . $id . ".");

        $paths = $this->calculateCmsPurgePaths($page);
        // Prepare purge path configs
        $path_configs = array();
        foreach ($paths as $path) {
            array_push($path_configs, array("condition" => $path, "name" => "URI", "operator" => "REGEX"));
        }
        $helper->flushPaths($path_configs);

        return $this;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Observer
     */
    public function checkout_cart_save_after($observer)
    {
        Mage::helper('yottaa_yottaa')->log("Cart save event captured.");
        Mage::helper('yottaa_yottaa/cookie')->setNoCacheCookie();
        return $this;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Observer
     */
    public function customer_login($observer)
    {
        Mage::helper('yottaa_yottaa')->log("Customer login event captured.");
        Mage::helper('yottaa_yottaa/cookie')->setNoCacheCookie();
        return $this;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Observer
     */
    public function customer_logout($observer)
    {
        Mage::helper('yottaa_yottaa')->log("Customer logout event captured.");
        Mage::helper('yottaa_yottaa/cookie')->deleteNoCacheCookie();
        return $this;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Observer
     */
    public function core_session_abstract_add_message($observer)
    {
        Mage::helper('yottaa_yottaa')->log("Add message event captured.");
        Mage::helper('yottaa_yottaa/cookie')->setNoCacheCookie();
        /*
        if ( Mage::getSingleton('catalog/session')->getMessages()->count() > 0 ) {
            $this->auto_flush_yottaa_cache($event);
        }
        */
        return $this;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Observer
     */
    public function wishlist_add_product($observer)
    {
        Mage::helper('yottaa_yottaa')->log("Wishlist add product event captured.");
        Mage::helper('yottaa_yottaa/cookie')->setNoCacheCookie();
        return $this;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Observer
     */
    public function wishlist_items_renewed($observer)
    {
        Mage::helper('yottaa_yottaa')->log("Wishlist items renewed event captured.");
        Mage::helper('yottaa_yottaa/cookie')->setNoCacheCookie();
        return $this;
    }
}