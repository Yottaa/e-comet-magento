<?php

/**
 * @file
 * Helper class for managing Yottaa cookie
 */

class Yottaa_Yottaa_Helper_Cookie extends Mage_Core_Helper_Abstract
{

    const NO_CACHE_COOKIE = 'EXTERNAL_NO_YOTTAA_CACHE';

    /**
     * Get Cookie object
     *
     * @return Mage_Core_Model_Cookie
     */
    public static function getCookie()
    {
        return Mage::getSingleton('core/cookie');
    }

    /**
     * Disable caching of this and all future request for this visitor
     *
     * @return Yottaa_Yottaa_Helper_Data
     */
    public function setNoCacheCookie($renewOnly = false)
    {
        if ($this->getCookie()->get(self::NO_CACHE_COOKIE)) {
            $this->getCookie()->renew(self::NO_CACHE_COOKIE);
        } elseif (!$renewOnly) {
            $this->getCookie()->set(self::NO_CACHE_COOKIE, 1);
        }
        return $this;
    }

    /**
     * Enable Yottaa page caching by removing no cache cookie.
     *
     * @return Yottaa_Yottaa_Helper_Data
     */
    public function deleteNoCacheCookie()
    {
        if ($this->getCookie()->get(self::NO_CACHE_COOKIE)) {
            $this->getCookie()->delete(self::NO_CACHE_COOKIE);
        }
        return $this;
    }

}