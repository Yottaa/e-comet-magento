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
        $output = $helper->curl_post_async("https://api.yottaa.com/partners/" . $user_id . "/accounts", array("first_name" => $first_name, "last_name" => $last_name, "email" => $email, "phone" => $phone, "site" => $site), "POST", $api_key);
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
        return $helper->post_processing_settings(json_decode($this->parseHttpResponse($output), true));
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
     * Post-process settings return from Yottaa service.
     *
     * @param $json_output
     * @return array
     */
    private function post_processing_settings($json_output)
    {
        if (!isset($json_output["error"])) {

            $site_pages_key = "/";
            $configure_pages_key1 = "admin/config";
            $configure_pages_key2 = "admin%252Fconfig";
            $edit_pages_key = "/edit";

            $site_pages_caching = 'unknown';
            $edit_pages_caching = 'unknown';
            $configure_pages_caching = 'unknown';
            $configure_pages_caching1 = 'unknown';
            $configure_pages_caching2 = 'unknown';

            $exclusions = '';

            $excluded_sess_cookie = 'unknown';

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
                                        if ($match["name"] == "Request-Header" && $match["header_name"] == "Cookie" && $match["condition"] == "SESS" && $match["type"] == "0" && $match["operator"] == "NOT-CONTAIN") {
                                            $excluded_sess_cookie = "set";
                                        }
                                        if ($match["condition"] == $edit_pages_key && $match["name"] == "URI" && $match["type"] == "0" && $match["operator"] == "CONTAIN") {
                                            $edit_pages_caching = $direction;
                                        }
                                        if ($match["condition"] == $configure_pages_key1 && $match["name"] == "URI" && $match["type"] == "0" && $match["operator"] == "CONTAIN") {
                                            $configure_pages_caching1 = $direction;
                                        }
                                        if ($match["condition"] == $configure_pages_key2 && $match["name"] == "URI" && $match["type"] == "0" && $match["operator"] == "CONTAIN") {
                                            $configure_pages_caching2 = $direction;
                                        }
                                    }
                                }
                                if ($configure_pages_caching1 == "excluded" && $configure_pages_caching2 == "excluded") {
                                    $configure_pages_caching = "excluded";
                                }
                                if ($site_pages_caching == "unknown" || $excluded_sess_cookie != "set") {
                                    $site_pages_caching = "unknown";
                                    $excluded_sess_cookie = "unknown";
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

            return array('site_pages_caching' => $site_pages_caching,
                         'edit_pages_caching' => $edit_pages_caching,
                         'configure_pages_caching' => $configure_pages_caching,
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

                Mage::getModel('core/config')->saveConfig('yottaa/yottaa_group/yottaa_user_id', $user_id);
                Mage::getModel('core/config')->saveConfig('yottaa/yottaa_group/yottaa_site_id', $site_id);
                Mage::getModel('core/config')->saveConfig('yottaa/yottaa_group/yottaa_api_key', $api_key);

                Mage::getConfig()->cleanCache();

                Mage::getSingleton('adminhtml/session')->addSuccess($message);

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
                Mage::log("Ouput from flushing cache :" . $json_output);
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