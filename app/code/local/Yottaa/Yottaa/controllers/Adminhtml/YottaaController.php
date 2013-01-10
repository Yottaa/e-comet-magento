<?php

class Yottaa_Yottaa_Adminhtml_YottaaController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Accepts provided http content, checks for a valid http response,
     * unchunks if needed, returns http content without headers on
     * success, false on any errors.
     *
     * @param null $content
     * @return bool|string
     */
    private function parseHttpResponse($content = null)
    {
        if (empty($content)) {
            return false;
        }
        // split into array, headers and content.
        $hunks = explode("\r\n\r\n", trim($content));
        if (!is_array($hunks) or count($hunks) < 2) {
            return false;
        }
        $header = $hunks[count($hunks) - 2];
        $body = $hunks[count($hunks) - 1];
        $headers = explode("\n", $header);
        unset($hunks);
        unset($header);
        if (!$this->validateHttpResponse($headers)) {
            return false;
        }
        if (in_array('Transfer-Coding: chunked', $headers)) {
            return trim($this->unchunkHttpResponse($body));
        } else {
            return trim($body);
        }
    }

    /**
     * Validate http responses by checking header.  Expects array of
     * headers as argument.  Returns boolean.
     *
     * @param null $headers
     * @return bool
     */
    private function validateHttpResponse($headers = null)
    {
        if (!is_array($headers) or count($headers) < 1) {
            return false;
        }
        switch (trim(strtolower($headers[0]))) {
            case 'http/1.0 100 ok':
            case 'http/1.0 200 ok':
            case 'http/1.1 100 ok':
            case 'http/1.1 200 ok':
                return true;
                break;
        }
        return false;
    }

    /**
     * Unchunk http content.  Returns unchunked content on success,
     * false on any errors...  Borrows from code posted above by
     * jbr at ya-right dot com.
     *
     * @param null $str
     * @return bool|null|string
     */
    private function unchunkHttpResponse($str = null)
    {
        if (!is_string($str) or strlen($str) < 1) {
            return false;
        }
        $eol = "\r\n";
        $add = strlen($eol);
        $tmp = $str;
        $str = '';
        do {
            $tmp = ltrim($tmp);
            $pos = strpos($tmp, $eol);
            if ($pos === false) {
                return false;
            }
            $len = hexdec(substr($tmp, 0, $pos));
            if (!is_numeric($len) or $len < 0) {
                return false;
            }
            $str .= substr($tmp, ($pos + $add), $len);
            $tmp = substr($tmp, ($len + $pos + $add));
            $check = trim($tmp);
        } while (!empty($check));
        unset($tmp);
        return $str;
    }

    /**
     * @param $url
     * @param $params
     * @param $method
     * @return string
     */
    private function curl_post_async($url, $params, $method, $api_key)
    {
        foreach ($params as $key => &$val) {
            if (is_array($val)) $val = implode(',', $val);
            $post_params[] = $key . '=' . urlencode($val);
        }
        $post_string = implode('&', $post_params);

        $parts = parse_url($url);

        $fp = fsockopen("ssl://" . $parts['host'],
                        isset($parts['port']) ? $parts['port'] : 443,
                        $errno, $errstr, 30);

        // Data goes in the path for a GET request
        $parts['path'] .= '?' . $post_string;

        $out = $method . " " . $parts['path'] . " HTTP/1.1\r\n";
        $out .= "Host: " . $parts['host'] . "\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out .= "Content-Length: 0\r\n";
        $out .= "YOTTAA-API-KEY: " . $api_key . "\r\n";
        $out .= "Connection: Close\r\n\r\n";

        fwrite($fp, $out);
        $result = "";
        while (!feof($fp)) {
            $result .= fgets($fp, 128);
        }
        fclose($fp);
        return $result;
    }

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
        $user_id = '4d34f75b74b1553ba500007f';
        $api_key = '455df7500258012f663b12313d145ceb';
        list($first_name, $last_name) = explode(" ", $full_name);
        $output = $this->curl_post_async("https://api.yottaa.com/partners/" . $user_id . "/accounts", array("first_name" => $first_name, "last_name" => $last_name, "email" => $email, "phone" => $phone, "site" => $site), "POST", $api_key);
        return json_decode($this->parseHttpResponse($output), true);
    }

    /**
     * Checks status of the registered Yottaa optimizer.
     *
     * @return mixed
     */
    private function check_yottaa_status()
    {
        $yottaa_user_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_user_id');
        $yottaa_api_key = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_api_key');
        $yottaa_site_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_site_id');
        $output = $this->curl_post_async("https://api.yottaa.com/sites/" . $yottaa_site_id, array("user_id" => $yottaa_user_id), "GET", $yottaa_api_key);
        return json_decode($this->parseHttpResponse($output), true);
    }

    /**
     * Resumes the registered Yottaa optimizer.
     *
     * @return mixed
     */
    private function resume_yottaa_optimizer()
    {
        $yottaa_user_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_user_id');
        $yottaa_api_key = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_api_key');
        $yottaa_site_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_site_id');
        $output = $this->curl_post_async("https://api.yottaa.com/optimizers/" . $yottaa_site_id . "/resume", array("user_id" => $yottaa_user_id), "PUT", $yottaa_api_key);
        return json_decode($this->parseHttpResponse($output), true);
    }

    /**
     * Pauses the registered Yottaa optimizer.
     *
     * @return mixed
     */
    private function pause_yottaa_optimizer()
    {
        $yottaa_user_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_user_id');
        $yottaa_api_key = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_api_key');
        $yottaa_site_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_site_id');
        $output = $this->curl_post_async("https://api.yottaa.com/optimizers/" . $yottaa_site_id . "/pause", array("user_id" => $yottaa_user_id), "PUT", $yottaa_api_key);
        return json_decode($this->parseHttpResponse($output), true);
    }

    /**
     * Fetches settings of the registered Yottaa optimizer.
     *
     * @return mixed
     */
    private function retrieve_yottaa_settings()
    {
        $yottaa_user_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_user_id');
        $yottaa_api_key = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_api_key');
        $yottaa_site_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_site_id');
        $output = $this->curl_post_async("https://api.yottaa.com/sites/" . $yottaa_site_id . "/settings", array("user_id" => $yottaa_user_id), "GET", $yottaa_api_key);
        return $this->post_processing_settings(json_decode($this->parseHttpResponse($output), true));
    }

    /**
     * Asks the registered Yottaa optimizer to flush caches.
     *
     * @return mixed
     */
    private function flush_yottaa_cache()
    {
        $yottaa_user_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_user_id');
        $yottaa_api_key = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_api_key');
        $yottaa_site_id = Mage::getStoreConfig('yottaa/yottaa_group/yottaa_site_id');
        $output = $this->curl_post_async("https://api.yottaa.com/optimizers/" . $yottaa_site_id . "/flush_cache", array("user_id" => $yottaa_user_id), "PUT", $yottaa_api_key);
        return json_decode($this->parseHttpResponse($output), true);
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
     * Automatically flushes cache for an updated node or a node whose comments have been updated,
     * created or deleted.
     *
     *
     * @param $node
     * @return void
     */
    private function auto_flush_yottaa_cache($node)
    {
        $yottaa_auto_clear_cache = variable_get('yottaa_auto_clear_cache', 1);
        if ($yottaa_auto_clear_cache == 1) {
            $json_output = flush_yottaa_cache();
            // drupal_set_message(t('Cache flushed!'));
            if (isset($json_output["error"])) {
                $error = $json_output["error"];
                form_set_error('', 'Error received from flushing Yottaa cache:' . json_encode($error));
            }
        }
    }

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

                $message = $this->__('New account form has been created with preview url') . '<a href="' . $preview_url . '">' . $preview_url . '</a>';

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


}