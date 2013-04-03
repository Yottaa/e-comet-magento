<?php

class Yottaa_Yottaa_Adminhtml_YottaaController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Creates a new Yottaa account.
     *
     * @param $full_name
     * @param $email
     * @param $phone
     * @param $site
     * @return mixed
     */
    private function create_yottaa_account($full_name, $email, $phone, $site)
    {
        $helper = Mage::helper('yottaa_yottaa');
        $user_id = '4d34f75b74b1553ba500007f';
        $api_key = '455df7500258012f663b12313d145ceb';
        list($first_name, $last_name) = explode(" ", $full_name);
        Mage::log("Creating new Yottaa acount with :" . $full_name . ' ' . $email . ' ' . $phone . ' ' . $site);
        $output = $helper->curl_post_async("https://api.yottaa.com/partners/" . $user_id . "/accounts", array("first_name" => $first_name, "last_name" => $last_name, "email" => $email, "phone" => $phone, "site" => $site), "POST", $api_key);
        Mage::log("New Yottaa acount with :" . json_encode($output));
        return json_decode($helper->parseHttpResponse($output), true);
    }

    /**
     * Checks status of the registered Yottaa optimizer.
     *
     * @return mixed
     */
    private function check_yottaa_status()
    {
        $helper = Mage::helper('yottaa_yottaa');
        $yottaa_user_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_user_id');
        $yottaa_api_key = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_api_key');
        $yottaa_site_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_site_id');
        $output = $helper->curl_post_async("https://api.yottaa.com/sites/" . $yottaa_site_id, array("user_id" => $yottaa_user_id), "GET", $yottaa_api_key);
        return json_decode($helper->parseHttpResponse($output), true);
    }

    /**
     * Resumes the registered Yottaa optimizer.
     *
     * @return mixed
     */
    private function resume_yottaa_optimizer()
    {
        $helper = Mage::helper('yottaa_yottaa');
        $yottaa_user_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_user_id');
        $yottaa_api_key = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_api_key');
        $yottaa_site_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_site_id');
        $output = $helper->curl_post_async("https://api.yottaa.com/optimizers/" . $yottaa_site_id . "/resume", array("user_id" => $yottaa_user_id), "PUT", $yottaa_api_key);
        return json_decode($helper->parseHttpResponse($output), true);
    }

    /**
     * Pauses the registered Yottaa optimizer.
     *
     * @return mixed
     */
    private function pause_yottaa_optimizer()
    {
        $helper = Mage::helper('yottaa_yottaa');
        $yottaa_user_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_user_id');
        $yottaa_api_key = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_api_key');
        $yottaa_site_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_site_id');
        $output = $helper->curl_post_async("https://api.yottaa.com/optimizers/" . $yottaa_site_id . "/pause", array("user_id" => $yottaa_user_id), "PUT", $yottaa_api_key);
        return json_decode($helper->parseHttpResponse($output), true);
    }

    /**
     * Fetches settings of the registered Yottaa optimizer.
     *
     * @return mixed
     */
    private function retrieve_yottaa_settings()
    {
        $helper = Mage::helper('yottaa_yottaa');
        $yottaa_user_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_user_id');
        $yottaa_api_key = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_api_key');
        $yottaa_site_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_site_id');
        $output = $helper->curl_post_async("https://api.yottaa.com/sites/" . $yottaa_site_id . "/settings", array("user_id" => $yottaa_user_id), "GET", $yottaa_api_key);
        return $this->post_processing_settings(json_decode($helper->parseHttpResponse($output), true));
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
     * Post-process settings return from Yottaa service.
     *
     * @param $json_output
     * @return array
     */
    private function post_processing_settings($json_output)
    {
        if (!isset($json_output["error"])) {

            $site_pages_key = ".html";
            $admin_pages_key = "/admin";
            $checkout_pages_key = "/checkout";

            $home_page_caching = 'unknown';
            $site_pages_caching = 'unknown';
            $admin_pages_caching = 'unknown';
            $checkout_pages_caching = 'unknown';

            $exclusions = '';

            if (isset($json_output["defaultActions"]) && isset($json_output["defaultActions"]["resourceActions"]) && isset($json_output["defaultActions"]["resourceActions"]["htmlCache"])) {
                $html_cachings = $json_output["defaultActions"]["resourceActions"]["htmlCache"];
                foreach ($html_cachings as &$html_caching) {
                    if (isset($html_caching["filters"])) {
                        $filters = $html_caching["filters"];
                        foreach ($filters as &$filter) {
                            if (isset($filter["match"])) {
                                $direction = $filter["direction"] == 1 ? "included" : "excluded";
                                $matches = $filter["match"];
                                foreach ($matches as &$match) {
                                    if (isset($match["condition"])) {
                                        if ($match["condition"] == $site_pages_key && $match["name"] == "URI" && $match["type"] == "0" && $match["operator"] == "CONTAIN") {
                                            $site_pages_caching = $direction;
                                        }
                                        if ($match["condition"] == $admin_pages_key && $match["name"] == "URI" && $match["type"] == "0" && $match["operator"] == "CONTAIN") {
                                            $admin_pages_caching = $direction;
                                        }
                                        if ($match["condition"] == $checkout_pages_key && $match["name"] == "URI" && $match["type"] == "0" && $match["operator"] == "CONTAIN") {
                                            $checkout_pages_caching = $direction;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (isset($json_output["defaultActions"]) && isset($json_output["defaultActions"]["filters"])) {
                $filters = $json_output["defaultActions"]["filters"];
                foreach ($filters as &$filter) {
                    if (isset($filter["match"])) {
                        if ($filter["direction"] == 0) {
                            $matches = $filter["match"];
                            foreach ($matches as &$match) {
                                if (isset($match["condition"])) {
                                    if ($exclusions != '') {
                                        $exclusions = $exclusions . ' ; ';
                                    }
                                    $exclusions = $exclusions . $match["condition"];
                                }
                            }
                        }
                    }
                }
            }

            if (isset($json_output["resourceRules"])) {
                $resourceRules = $json_output["resourceRules"];
                foreach ($resourceRules as &$resourceRule) {
                    if (isset($resourceRule["special_type"]) && $resourceRule["special_type"] == "home") {
                        if ($resourceRule["enabled"]) {
                            $home_page_caching = 'included';
                        }
                    }
                }
            }

            return array('home_page_caching' => $home_page_caching,
                         'site_pages_caching' => $site_pages_caching,
                         'admin_pages_caching' => $admin_pages_caching,
                         'checkout_pages_caching' => $checkout_pages_caching,
                         'exclusions' => $exclusions);
        } else {
            return $json_output;
        }
    }

    /**
     * @return void
     */
    public function indexAction()
    {
        $this->loadLayout();

        // Sets the window title to "Yottaa / System / Magento Admin"
        $this->_title($this->__('System'))
                ->_title($this->__('Yottaa'))
        // Highlight the current menu
                ->_setActiveMenu('system');

        $yottaa_user_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_user_id');
        $yottaa_api_key = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_api_key');
        $yottaa_site_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_site_id');

        $yottaa_auto_clear_cache = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_auto_clear_cache');

        if (!isset($yottaa_auto_clear_cache)) {
            $yottaa_auto_clear_cache = 0;
        }

        $config = $this->getLayout()->getBlock('config');

        $config->assign('yottaa_user_id', $yottaa_user_id);
        $config->assign('yottaa_api_key', $yottaa_api_key);
        $config->assign('yottaa_site_id', $yottaa_site_id);

        $config->assign('yottaa_auto_clear_cache', $yottaa_auto_clear_cache);

        $new_yottaa_account = false;
        if (empty($yottaa_user_id) || empty($yottaa_api_key) || empty($yottaa_site_id)) {
            // offer new account form as well as existing user form link
            $new_yottaa_account = true;
        } else {
            $json_output = $this->check_yottaa_status();

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

            $json_output2 = $this->retrieve_yottaa_settings();

            if (!isset($json_output2["error"])) {
                $config->assign('yottaa_settings_status', 'ok');
                $config->assign('yottaa_settings_home_page_caching', $json_output2["home_page_caching"]);
                $config->assign('yottaa_settings_site_pages_caching', $json_output2["site_pages_caching"]);
                $config->assign('yottaa_settings_admin_pages_caching', $json_output2["admin_pages_caching"]);
                $config->assign('yottaa_settings_checkout_pages_caching', $json_output2["checkout_pages_caching"]);

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

    public function postAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }

            $yottaa_user_name = $post['yottaa_user_name'];
            $yottaa_user_phone = $post['yottaa_user_phone'];
            $yottaa_user_email = $post['yottaa_user_email'];
            $yottaa_site_name = $post['yottaa_site_name'];

            $json_output = $this->create_yottaa_account($yottaa_user_name, $yottaa_user_email, $yottaa_user_phone, $yottaa_site_name);

            if (!isset($json_output["error"])) {
                $user_id = $json_output["user_id"];
                $site_id = $json_output["site_id"];
                $api_key = $json_output["api_key"];
                $preview_url = $json_output["preview_url"];

                $message = $this->__('New Yottaa account has been created with ') . '<a href="' . $preview_url . '">preview url</a>.';

                $message2 = $this->__('Your Yottaa login information has been sent to your email address ') . $yottaa_user_email .'.';

                Mage::getModel('core/config')->saveConfig('yottaa/yottaa_group/yottaa_user_id', $user_id);
                Mage::getModel('core/config')->saveConfig('yottaa/yottaa_group/yottaa_site_id', $site_id);
                Mage::getModel('core/config')->saveConfig('yottaa/yottaa_group/yottaa_api_key', $api_key);

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

    public function postSettingsAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }

            $yottaa_auto_clear_cache = $post['yottaa_auto_clear_cache'];

            $status = intval($yottaa_auto_clear_cache) == 1 ? 'enabled' : 'disabled';

            Mage::getModel('core/config')->saveConfig('yottaa/yottaa_group/yottaa_auto_clear_cache', intval($yottaa_auto_clear_cache));

            $message = $this->__('Automatically clearing Yottaa\'s site optimizer cache is ') . $status . '.';

            Mage::getConfig()->cleanCache();

            Mage::getSingleton('adminhtml/session')->addSuccess($message);

        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }

    public function postActionsAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }

            $yottaa_action_key = $post['yottaa_action_key'];

            if ($yottaa_action_key == 'resume') {
                $json_output = $this->resume_yottaa_optimizer();
                if (!isset($json_output["error"])) {
                    Mage::getSingleton('adminhtml/session')->addSuccess('Your Yottaa optimizer has been resumed.');
                } else {
                    $error = $json_output["error"];
                    Mage::getSingleton('adminhtml/session')->addError('Error received from resuming Yottaa optimizer:' . json_encode($error));
                }
            } else if ($yottaa_action_key == 'pause') {
                $json_output = $this->pause_yottaa_optimizer();
                if (!isset($json_output["error"])) {
                    Mage::getSingleton('adminhtml/session')->addSuccess('Your Yottaa optimizer has been paused.');
                } else {
                    $error = $json_output["error"];
                    Mage::getSingleton('adminhtml/session')->addError('Error received from pausing Yottaa optimizer:' . json_encode($error));
                }
            } else if ($yottaa_action_key == 'clear_cache') {
                $json_output = $this->flush_yottaa_cache();
                Mage::log("Output from flushing cache :" . json_encode($json_output));
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