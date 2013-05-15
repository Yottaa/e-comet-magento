<?php

class Yottaa_Yottaa_Adminhtml_YottaaController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return void
     */
    public function indexAction()
    {
        $helper = Mage::helper('yottaa_yottaa');

        $this->loadLayout();

        $this->_title($this->__('System'))->_title($this->__('Yottaa'))->_setActiveMenu('system');

        $parameters = $helper->getParameters();

        $yottaa_site_id = $parameters['site_id'];
        $yottaa_user_id = $parameters['user_id'];
        $yottaa_api_key = $parameters['api_key'];

        $yottaa_auto_clear_cache = $helper->getAutoClearCacheParameter();
        $yottaa_enable_logging = $helper->getEnableLoggingParameter();

        $yottaa_purge_parent_products = $helper->getPurgeParentProductsParameter();
        $yottaa_purge_product_categories = $helper->getPurgeProductCategoriesParameter();

        $config = $this->getLayout()->getBlock('config');

        $config->assign(Yottaa_Yottaa_Helper_Data::USER_ID_CONFIG, $yottaa_user_id);
        $config->assign(Yottaa_Yottaa_Helper_Data::API_KEY_CONFIG, $yottaa_api_key);
        $config->assign(Yottaa_Yottaa_Helper_Data::SITE_ID_CONFIG, $yottaa_site_id);

        $config->assign(Yottaa_Yottaa_Helper_Data::AUTO_CLEAR_CACHE_CONFIG, $yottaa_auto_clear_cache);
        $config->assign(Yottaa_Yottaa_Helper_Data::ENABLE_LOGGING_CONFIG, $yottaa_enable_logging);
        $config->assign(Yottaa_Yottaa_Helper_Data::PURGE_PARENT_PRODUCTS_CONFIG, $yottaa_purge_parent_products);
        $config->assign(Yottaa_Yottaa_Helper_Data::PURGE_PRODUCT_CATEGORIES_CONFIG, $yottaa_purge_product_categories);

        $new_yottaa_account = false;
        if (empty($yottaa_user_id) || empty($yottaa_api_key) || empty($yottaa_site_id)) {
            // offer new account form as well as existing user form link
            $new_yottaa_account = true;
        } else {
            $json_output = $helper->getStatus();

            if (!isset($json_output["error"])) {
                $yottaa_status = $json_output["optimizer"];
                $yottaa_preview_url = $json_output["preview_url"];
                $config->assign('yottaa_status', $yottaa_status);
                $config->assign('yottaa_preview_url', $yottaa_preview_url);
            } else {
                $error = $json_output["error"];
                $config->assign('yottaa_status', 'error');
                $config->assign('yottaa_status_error', json_encode($error));
            }

            $json_output2 = $helper->getSettings();

            if (!isset($json_output2["error"])) {
                $config->assign('yottaa_settings_status', 'ok');
                $config->assign('yottaa_settings_home_page_caching', $json_output2["home_page_caching"]);
                $config->assign('yottaa_settings_site_pages_caching', $json_output2["site_pages_caching"]);
                $config->assign('yottaa_settings_admin_pages_caching', $json_output2["admin_pages_caching"]);
                $config->assign('yottaa_settings_checkout_pages_caching', $json_output2["checkout_pages_caching"]);
                $config->assign('yottaa_settings_only_cache_anonymous_users', $json_output2["only_cache_anonymous_users"]);

                if (strrpos($json_output2["exclusions"],'/admin') === false) {
                    $config->assign('yottaa_settings_admin_pages_optimization', 'unknown');
                } else {
                    $config->assign('yottaa_settings_admin_pages_optimization', 'excluded');
                }

            } else {
                $error = $json_output2["error"];
                $config->assign('yottaa_settings_status', 'error');
                $config->assign('yottaa_settings_status_error', json_encode($error));
            }
        }
        $config->assign('new_yottaa_account', $new_yottaa_account);

        $this->renderLayout();
    }

    /**
     * @return void
     */
    public function postAction()
    {
        $helper = Mage::helper('yottaa_yottaa');
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }

            $yottaa_user_name = $post['yottaa_user_name'];
            $yottaa_user_phone = $post['yottaa_user_phone'];
            $yottaa_user_email = $post['yottaa_user_email'];
            $yottaa_site_name = $post['yottaa_site_name'];

            $helper->log("Creating new Yottaa acount with :" . $yottaa_user_name . ' ' . $yottaa_user_email . ' ' . $yottaa_user_phone . ' ' . $yottaa_site_name);
            $json_output = $helper->createAccount($yottaa_user_name, $yottaa_user_email, $yottaa_user_phone, $yottaa_site_name);
            $helper->log("New Yottaa acount with :" . json_encode($json_output));

            if (!isset($json_output["error"])) {
                $user_id = $json_output["user_id"];
                $site_id = $json_output["site_id"];
                $api_key = $json_output["api_key"];
                $preview_url = $json_output["preview_url"];

                $message = $this->__('New Yottaa account has been created with ') . '<a href="' . $preview_url . '">preview url</a>.';
                $message2 = $this->__('Your Yottaa login information has been sent to your email address ') . $yottaa_user_email .'.';

                $helper->updateParameters($api_key, $user_id, $site_id);

                Mage::getConfig()->cleanCache();
                Mage::getSingleton('adminhtml/session')->addSuccess($message);
                Mage::getSingleton('adminhtml/session')->addSuccess($message2);
            } else {
                $error = $json_output["error"];
                Mage::getSingleton('adminhtml/session')->addError('Error received from creating Yottaa user:' . json_encode($error));
            }

        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }

    /**
     * @return void
     */
    public function postSettingsAction()
    {
        $helper = Mage::helper('yottaa_yottaa');
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }

            $yottaa_auto_clear_cache = $post['yottaa_auto_clear_cache'];
            $status = intval($yottaa_auto_clear_cache) == 1 ? 'enabled' : 'disabled';
            $helper->setAutoClearCacheParameter($yottaa_auto_clear_cache);
            $message = $this->__('Automatically clearing Yottaa\'s site optimizer cache is ') . $status . '.';

            $yottaa_enable_logging = $post['yottaa_enable_logging'];
            $status = intval($yottaa_enable_logging) == 1 ? 'enabled' : 'disabled';
            $helper->setEnableLoggingParameter($yottaa_enable_logging);
            $message = $message . '<br/>' . $this->__('Logging for Yottaa service calls is ') . $status . '.';

            $yottaa_purge_parent_products = $post['yottaa_purge_parent_products'];
            $status = intval($yottaa_purge_parent_products) == 1 ? 'enabled' : 'disabled';
            $helper->setPurgeParentProductsParameter($yottaa_purge_parent_products);
            $message = $message . '<br/>' . $this->__('Purging caches for parent products is ') . $status . '.';

            $yottaa_purge_product_categories = $post['yottaa_purge_product_categories'];
            $status = intval($yottaa_purge_product_categories) == 1 ? 'enabled' : 'disabled';
            $helper->setPurgeProductCategoriesParameter($yottaa_purge_product_categories);
            $message = $message . '<br/>' . $this->__('Purging caches for product categories is ') . $status . '.';

            Mage::getConfig()->cleanCache();
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }

    /**
     * @return void
     */
    public function postActionsAction()
    {
        $helper = Mage::helper('yottaa_yottaa');
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }

            $yottaa_action_key = $post['yottaa_action_key'];

            if ($yottaa_action_key == 'resume') {
                $json_output = $helper->resume();
                if (!isset($json_output["error"])) {
                    Mage::getSingleton('adminhtml/session')->addSuccess('Your Yottaa optimizer has been resumed.');
                } else {
                    $error = $json_output["error"];
                    Mage::getSingleton('adminhtml/session')->addError('Error received from resuming Yottaa optimizer:' . json_encode($error));
                }
            } else if ($yottaa_action_key == 'pause') {
                $json_output = $helper->pause();
                if (!isset($json_output["error"])) {
                    Mage::getSingleton('adminhtml/session')->addSuccess('Your Yottaa optimizer has been paused.');
                } else {
                    $error = $json_output["error"];
                    Mage::getSingleton('adminhtml/session')->addError('Error received from pausing Yottaa optimizer:' . json_encode($error));
                }
            } else if ($yottaa_action_key == 'clear_cache') {
                $json_output = $helper->flush();
                $helper->log("Output from flushing cache :" . json_encode($json_output));
                if (!isset($json_output["error"])) {
                    Mage::getSingleton('adminhtml/session')->addSuccess('Your Yottaa cache has been cleared.');
                } else {
                    $error = $json_output["error"];
                    Mage::getSingleton('adminhtml/session')->addError('Error received from clearing Yottaa cache:' . json_encode($error));
                }
            }

        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }
}