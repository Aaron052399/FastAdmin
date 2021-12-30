<?php

namespace app\common\library;

/**
 * Curl类
 */
class Curl
{
    private $_ch;

    private $_url;

    function __construct()
    {
        $this->_ch = curl_init();
    }

    private function _setUrl($url)
    {
        $this->_url = $url;
        curl_setopt($this->_ch, CURLOPT_URL, $this->_url);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);

        if (false !== strstr($this->_url, 'https://', true)) {
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, 2);
        }
    }

    /*************    以下对外方法    *****************/

    public function post($url = "", $post_params = array(), $header = "", $referer = "", $cookie_file = "")
    {
        $this->_setUrl($url);
        curl_setopt($this->_ch, CURLOPT_POST, 1);
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $post_params);

        if (!empty($header)) {
            curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $header);
        }

        if (!empty($referer)) {
            curl_setopt($this->_ch, CURLOPT_REFERER, $referer);
        }

        if (!empty($cookie_file)) {
            curl_setopt($this->_ch, CURLOPT_COOKIEFILE, $cookie_file);
        }
        $output = curl_exec($this->_ch);
        curl_close($this->_ch);
        return $output;
    }


    public function get($url = "", $header = 0)
    {
        $this->_setUrl($url);
        curl_setopt($this->_ch, CURLOPT_HEADER, $header);
        $output = curl_exec($this->_ch);
//        if (empty($output)) {
//            $error = curl_errno($this->_ch);
//            $error_str = curl_error($this->_ch);
//            $date = date('Y-m-d H:i:s');
//            echo $error_str;
//            error_log("$date 出现一次curl {$error}:{$error_str}  url:{$url}\n", 3, "/data/webfile/static/curl_error.log");
//        }

        curl_close($this->_ch);
        return $output;
    }
}
