<?php

class Yottaa_Yottaa_Model_Product_Observer
{
    public function __construct()
    {
    }

    /**
     * Asks the registered Yottaa optimizer to flush caches.
     *
     * @return mixed
     */
    private function flush_yottaa_cache()
    {
        $helper = Mage::helper('yottaa_yottaa');
        $yottaa_user_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_user_id');
        $yottaa_api_key = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_api_key');
        $yottaa_site_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_site_id');
        $output = $helper->curl_post_async("https://api.yottaa.com/sites/" . $yottaa_site_id . "/flush_cache", array("user_id" => $yottaa_user_id), "PUT", $yottaa_api_key);
        return json_decode($helper->parseHttpResponse($output), true);
    }

    /**
     * Automatically flushes cache for an updated node or a node whose comments have been updated,
     * created or deleted.
     *
     *
     * @param $product
     * @return void
     */
    private function auto_flush_yottaa_cache($product)
    {
        $yottaa_auto_clear_cache = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_auto_clear_cache');
        if (!isset($yottaa_auto_clear_cache)) {
            $yottaa_auto_clear_cache = 0;
        }
        if ($yottaa_auto_clear_cache == 1) {
            $json_output = $this->flush_yottaa_cache();
            if (isset($json_output["error"])) {
                Mage::log('Failed to flush Yottaa cache.');
                Mage::log($json_output["error"]);
            } else {
                Mage::log('Yottaa cache has been successfully flushed!');
            }
        }
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Product_Observer
     */
    public function catalog_product_save_after($observer)
    {
        $event = $observer->getEvent();
        $product = $event->getProduct();

        $message = "Product save event captured.";
        Mage::log($message);

        $this->auto_flush_yottaa_cache($product);

        return $this;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Product_Observer
     */
    public function catalog_category_save_after($observer)
    {
        $event = $observer->getEvent();
        $category = $event->getCategory();

        $message = "Category save event captured.";
        Mage::log($message);

        $this->auto_flush_yottaa_cache($category);

        return $this;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Product_Observer
     */
    public function checkout_cart_save_after($observer)
    {
        $event = $observer->getEvent();
        $cart = $event->getCart();

        $message = "Cart save event captured.";
        Mage::log($message);

        $this->auto_flush_yottaa_cache($cart);

        return $this;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Product_Observer
     */
    public function customer_login($observer)
    {
        $event = $observer->getEvent();
        $customer = $event->getCustomer();

        $message = "Customer login event captured.";
        Mage::log($message);

        $helper = Mage::helper('yottaa_yottaa');

        $helper->setNoCacheCookie();

        //$this->auto_flush_yottaa_cache($customer);

        return $this;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Product_Observer
     */
    public function customer_logout($observer)
    {
        $event = $observer->getEvent();
        $customer = $event->getCustomer();

        $message = "Customer logout event captured.";
        Mage::log($message);

        $helper = Mage::helper('yottaa_yottaa');

        $helper->deleteNoCacheCookie();

        //$this->auto_flush_yottaa_cache($customer);

        return $this;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Product_Observer
     */
    public function core_session_abstract_add_message($observer)
    {
        $event = $observer->getEvent();

        $message = "Add message event captured.";
        Mage::log($message);

        if ( Mage::getSingleton('catalog/session')->getMessages()->count() > 0 ) {
            $this->auto_flush_yottaa_cache($event);
        }

        return $this;
    }

    /**
     * Triggers Yottaa cache refreshing
     *
     * @param   Varien_Event_Observer $observer
     * @return  Yottaa_Yottaa_Model_Product_Observer
     */
    public function wishlist_items_renewed($observer)
    {
        $event = $observer->getEvent();

        $message = "Wishlist items renewed event captured.";
        Mage::log($message);

        $this->auto_flush_yottaa_cache($event);

        return $this;
    }
}