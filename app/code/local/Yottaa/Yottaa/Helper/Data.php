<?PHP

class Yottaa_Yottaa_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Accepts provided http content, checks for a valid http response,
     * unchunks if needed, returns http content without headers on
     * success, false on any errors.
     *
     * @param null $content
     * @return bool|string
     */
    public function parseHttpResponse($content = null)
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
            return '{"error" : ' . trim($body) . '}';
            //return false;
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
    public function curl_post_async($url, $params, $method, $api_key)
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