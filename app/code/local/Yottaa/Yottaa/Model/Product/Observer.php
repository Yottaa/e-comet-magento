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
        $output = $helper->curl_post_async("https://api.yottaa.com/optimizers/" . $yottaa_site_id . "/flush_cache", array("user_id" => $yottaa_user_id), "PUT", $yottaa_api_key);
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
}