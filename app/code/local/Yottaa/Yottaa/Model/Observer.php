<?php

class Yottaa_Yottaa_Model_Observer
{
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
        return $helper->flush();
    }

    /**
     * Automatically flushes cache for an updated node or a node whose comments have been updated,
     * created or deleted.
     *
     *
     * @param $product
     * @return void
     */
    private function auto_flush_yottaa_cache($path_configs)
    {
        $helper = Mage::helper('yottaa_yottaa');
        if ($helper->getAutoClearCacheParameter() == 1) {
            $json_output = $helper->flushPaths($path_configs);
            if (isset($json_output["error"])) {
                $helper->log('Failed to flush Yottaa cache.');
                $helper->log($json_output["error"]);
            } else {
                $helper->log('Yottaa cache has been successfully flushed!');
            }
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
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Observer
     */
    public function catalog_product_save_after($observer)
    {
        $helper = Mage::helper('yottaa_yottaa');
        $event = $observer->getEvent();
        $product = $event->getProduct();

        $message = "Product save event captured.";
        $helper->log($message);

        $id = $product->getId();
        $paths = $this->calculateProductPurgePaths($id);
        // Purge parent product
        $purgeParentProducts = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_purge_parent_products_cache');
        $purgeCategories = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_purge_product_categories_cache');
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
        $event = $observer->getEvent();
        $category = $event->getCategory();

        $message = "Category save event captured.";
        $helper->log($message);

        $id = $category->getId();
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
    public function checkout_cart_save_after($observer)
    {
        $helper = Mage::helper('yottaa_yottaa');

        $event = $observer->getEvent();
        $cart = $event->getCart();

        $message = "Cart save event captured.";
        $helper->log($message);

        $cookie_helper = Mage::helper('yottaa_yottaa/cookie');

        $cookie_helper->setNoCacheCookie();

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
        $helper = Mage::helper('yottaa_yottaa');
        $event = $observer->getEvent();
        $customer = $event->getCustomer();

        $message = "Customer login event captured.";
        $helper->log($message);

        $cookie_helper = Mage::helper('yottaa_yottaa/cookie');

        $cookie_helper->setNoCacheCookie();

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
        $helper = Mage::helper('yottaa_yottaa');
        $event = $observer->getEvent();
        $customer = $event->getCustomer();

        $message = "Customer logout event captured.";
        $helper->log($message);

        $cookie_helper = Mage::helper('yottaa_yottaa/cookie');

        $cookie_helper->setNoCacheCookie();

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
        $helper = Mage::helper('yottaa_yottaa');
        $event = $observer->getEvent();

        $message = "Add message event captured.";
        $helper->log($message);

        $cookie_helper = Mage::helper('yottaa_yottaa/cookie');

        $cookie_helper->setNoCacheCookie();

        /*
        if ( Mage::getSingleton('catalog/session')->getMessages()->count() > 0 ) {
            $this->auto_flush_yottaa_cache($event);
        }
        */

        return $this;
    }

    public function wishlist_add_product($observer)
    {
        $helper = Mage::helper('yottaa_yottaa');
        $event = $observer->getEvent();

        $message = "Wishlist add product event captured.";
        $helper->log($message);

        $helper = Mage::helper('yottaa_yottaa/cookie');

        $helper->setNoCacheCookie();

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
        $helper = Mage::helper('yottaa_yottaa');
        $event = $observer->getEvent();

        $message = "Wishlist items renewed event captured.";
        $helper->log($message);

        $cookie_helper = Mage::helper('yottaa_yottaa/cookie');

        $cookie_helper->setNoCacheCookie();
        return $this;
    }
}