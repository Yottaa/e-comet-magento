<?PHP

class Yottaa_Yottaa_Helper_Data extends Yottaa_Yottaa_Helper_API
{
    const API_KEY_CONFIG = 'yottaa_api_key';
    const USER_ID_CONFIG = 'yottaa_user_id';
    const SITE_ID_CONFIG = 'yottaa_site_id';
    const AUTO_CLEAR_CACHE_CONFIG = 'yottaa_auto_clear_cache';
    const ENABLE_LOGGING_CONFIG = 'yottaa_enable_logging';
    const PURGE_PARENT_PRODUCTS_CONFIG = 'yottaa_purge_parent_products';
    const PURGE_PRODUCT_CATEGORIES_CONFIG = 'yottaa_purge_product_categories';

    const YOTTAA_API_KEY_CONFIG = 'yottaa/yottaa_group/yottaa_api_key';
    const YOTTAA_USER_ID_CONFIG = 'yottaa/yottaa_group/yottaa_user_id';
    const YOTTAA_SITE_ID_CONFIG = 'yottaa/yottaa_group/yottaa_site_id';
    const YOTTAA_AUTO_CLEAR_CACHE_CONFIG = 'yottaa/yottaa_group/yottaa_auto_clear_cache';
    const YOTTAA_ENABLE_LOGGING_CONFIG = 'yottaa/yottaa_group/yottaa_enable_logging';
    const YOTTAA_PURGE_PARENT_PRODUCTS_CONFIG = 'yottaa/yottaa_group/yottaa_purge_parent_products';
    const YOTTAA_PURGE_PRODUCT_CATEGORIES_CONFIG = 'yottaa/yottaa_group/yottaa_purge_product_categories';

    /**
     * Constructor.
     */
    public function __construct()
    {
        //$this->uid = Mage::getStoreConfig(self::YOTTAA_USER_ID_CONFIG);
        //$this->key = Mage::getStoreConfig(self::YOTTAA_API_KEY_CONFIG);
        //$this->sid = Mage::getStoreConfig(self::YOTTAA_SITE_ID_CONFIG);

        $uid = Mage::getStoreConfig(self::YOTTAA_USER_ID_CONFIG);
        $key = Mage::getStoreConfig(self::YOTTAA_API_KEY_CONFIG);
        $sid = Mage::getStoreConfig(self::YOTTAA_SITE_ID_CONFIG);

        parent::__construct($key, $uid, $sid);
    }

    /**
     * Post-processes Yottaa site settings.
     *
     * @param $json_output
     * @return array
     */
    protected function postProcessingSettings($json_output)
    {
        if (!isset($json_output["error"])) {

            $full_pages_key = "(.*)";
            $site_pages_key = ".html";
            $admin_pages_key = "/admin";
            $checkout_pages_key = "/checkout";

            $home_page_caching = 'unknown';
            $site_pages_caching = 'unknown';
            $admin_pages_caching = 'unknown';
            $checkout_pages_caching = 'unknown';

            $only_cache_anonymous_users = 'unknown';

            $exclusions = '';
            $excluded_cookie = 'unknown';

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
                                        if ($match["condition"] == $full_pages_key && $match["name"] == "URI" && $match["type"] == "0" && $match["operator"] == "REGEX") {
                                            $only_cache_anonymous_users = $direction;
                                        }
                                        if ($match["name"] == "Request-Header" && $match["header_name"] == "Cookie" && $match["condition"] == "EXTERNAL_NO_YOTTAA_CACHE" && $match["type"] == "0" && $match["operator"] == "CONTAIN") {
                                            $excluded_cookie = "set";
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
                if ($only_cache_anonymous_users == "unknown" || $excluded_cookie != "set") {
                    $only_cache_anonymous_users = "unknown";
                    $excluded_cookie = "unknown";
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
                         'only_cache_anonymous_users' => $only_cache_anonymous_users,
                         'exclusions' => $exclusions);
        } else {
            return $json_output;
        }
    }

    /**
     * Logs a message.
     *
     * @param $message
     * @return void
     */
    public function log($message)
    {
        if ($this->getEnableLoggingParameter() == 1) {
            Mage::log($message);
        }
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return array("api_key" => Mage::getStoreConfig(self::YOTTAA_API_KEY_CONFIG),
                     "user_id" => Mage::getStoreConfig(self::YOTTAA_USER_ID_CONFIG),
                     "site_id" => Mage::getStoreConfig(self::YOTTAA_SITE_ID_CONFIG),
        );
    }

    /**
     * @param $key
     * @param $uid
     * @param $sid
     * @return void
     */
    public function updateParameters($key, $uid, $sid)
    {
        Mage::getModel('core/config')->saveConfig(self::YOTTAA_API_KEY_CONFIG, $key);
        Mage::getModel('core/config')->saveConfig(self::YOTTAA_USER_ID_CONFIG, $uid);
        Mage::getModel('core/config')->saveConfig(self::YOTTAA_SITE_ID_CONFIG, $sid);
    }

    /**
     * @return
     */
    public function getEnableLoggingParameter()
    {
        return Mage::getStoreConfig(self::YOTTAA_ENABLE_LOGGING_CONFIG);
    }

    /**
     * @param $enabled
     * @return void
     */
    public function setEnableLoggingParameter($enabled)
    {
        Mage::getModel('core/config')->saveConfig(self::YOTTAA_ENABLE_LOGGING_CONFIG, intval($enabled));
    }

    /**
     * @return
     */
    public function getAutoClearCacheParameter()
    {
        return Mage::getStoreConfig(self::YOTTAA_AUTO_CLEAR_CACHE_CONFIG);
    }

    /**
     * @param $enabled
     * @return void
     */
    public function setAutoClearCacheParameter($enabled)
    {
        Mage::getModel('core/config')->saveConfig(self::YOTTAA_AUTO_CLEAR_CACHE_CONFIG, intval($enabled));
    }

    /**
     * @return
     */
    public function getPurgeParentProductsParameter()
    {
        return Mage::getStoreConfig(self::YOTTAA_PURGE_PARENT_PRODUCTS_CONFIG);
    }

    /**
     * @param $enabled
     * @return void
     */
    public function setPurgeParentProductsParameter($enabled)
    {
        Mage::getModel('core/config')->saveConfig(self::YOTTAA_PURGE_PARENT_PRODUCTS_CONFIG, intval($enabled));
    }

    /**
     * @return
     */
    public function getPurgeProductCategoriesParameter()
    {
        return Mage::getStoreConfig(self::YOTTAA_PURGE_PRODUCT_CATEGORIES_CONFIG);
    }

    /**
     * @param $enabled
     * @return void
     */
    public function setPurgeProductCategoriesParameter($enabled)
    {
        Mage::getModel('core/config')->saveConfig(self::YOTTAA_PURGE_PRODUCT_CATEGORIES_CONFIG, intval($enabled));
    }
}