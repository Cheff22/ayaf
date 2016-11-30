<?php
namespace Addons\Grab;
class GrabUtil {
    /* $options = array(
      CURLOPT_RETURNTRANSFER => true,         // return web page
      CURLOPT_HEADER         => false,        // don't return headers
      CURLOPT_FOLLOWLOCATION => true,         // follow redirects
      CURLOPT_ENCODING       => "",           // handle all encodings
      CURLOPT_USERAGENT      => "spider",     // who am i
      CURLOPT_AUTOREFERER    => true,         // set referer on redirect
      CURLOPT_CONNECTTIMEOUT => 120,          // timeout on connect
      CURLOPT_TIMEOUT        => 120,          // timeout on response
      CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
      CURLOPT_POST           => 1,            // i am sending post data
      CURLOPT_POSTFIELDS     => 'foo=bar&foo2=bar',    // this are my post vars
      CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl
      CURLOPT_SSL_VERIFYPEER => false,        //
      CURLOPT_VERBOSE        => 1                //
      //代理相关
      CURLOPT_PROXYTYPE=>CURLPROXY_SOCKSS,
      CURLOPT_PROXY=>'test.org:1080',
      CURLOPT_HTTPPROXYTUNNEL=>1,
      CURLOPT_COOKIEJAR=>cookie.txt
      );
     */
    private function is_json($string){
        return ((is_string($string) && (is_object(json_decode($string)) || is_array(json_decode($string))))) ? true : false;
    }

    //单个抓取
    public static function single_grab_json($url) {
        if (empty($url)){return false;}
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );
        curl_setopt_array($ch, $options);
        $tmpResult = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //echo $httpCode;
        //echo $tmpResult;
        if ($httpCode == 200) {
            $res = json_decode($tmpResult, true);
        }else{
            return false;
        }
        curl_close($ch);
        return $res;
    }
    
    //单个抓取
    public static function single_grab_json_postdata($url,$postfields) {
        $post_data = '';
        foreach($postfields as $key=>$value){
            $post_data .="$key=".urlencode($value)."&";  
        }
        if (empty($url)){return false;}
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT=>"spider",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>substr($post_data,0,-1)
        );
        curl_setopt_array($ch, $options);
        $tmpResult = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //echo $httpCode;
        //echo $tmpResult;
        if ($httpCode == 200) {
            $res = json_decode($tmpResult, true);
            if(json_last_error() != JSON_ERROR_NONE){
                $res=$tmpResult;
            }         
        }else{
            return false;
        }
        curl_close($ch);
        return $res;
    }
    
    //单个抓取xml数据
    public static function single_grab_xml_postdata($url,$postfields) {
        if (empty($url)){return false;}
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            //CURLOPT_HEADER=>array("Content-type: text/xml"),
            CURLOPT_TIMEOUT=>30,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>$postfields,
        );
        curl_setopt_array($ch, $options);
        $tmpResult = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //echo $httpCode;
        //echo $tmpResult;
        if ($httpCode == 200) {
            $res=(array)simplexml_load_string($tmpResult, 'SimpleXMLElement', LIBXML_NOCDATA);
        }else{
            return false;
        }
        curl_close($ch);
        return $res;
    }
      
    //单个抓取xml数据
    public static function single_grab_xml_postdata_ssl($url,$postfields,$cert='',$key='') {
        if (empty($url)){return false;}
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            //CURLOPT_HEADER=>array("Content-type: text/xml"),
            CURLOPT_TIMEOUT=>30,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSLCERT=>$cert,
            CURLOPT_SSLKEY=>$key,
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>$postfields,
        );
        curl_setopt_array($ch, $options);
        $tmpResult = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //echo $httpCode;
        //echo $tmpResult;
        if ($httpCode == 200) {
            $res=(array)simplexml_load_string($tmpResult, 'SimpleXMLElement', LIBXML_NOCDATA);
        }else{
            return false;
        }
        curl_close($ch);
        return $res;
    }

    //多线程抓取
    public static function multiple_grab_json($nodes) {
        if (empty($nodes)){return false;}
        $mh = curl_multi_init();
        $curl_array = array();
        foreach ($nodes as $i => $url) {
            $curl_array[$i] = curl_init($url);
            $options = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            );
            curl_setopt_array($curl_array[$i], $options);
            curl_multi_add_handle($mh, $curl_array[$i]);
        }
        $running = NULL;
        do {
            usleep(10000);
            curl_multi_exec($mh, $running);
        } while ($running > 0);

        $res = array();
        foreach ($nodes as $i => $url) {
            $tmp_result = curl_multi_getcontent($curl_array[$i]);
            $res[$url] = json_decode($tmp_result, true);
        }
        foreach ($nodes as $i => $url) {
            curl_multi_remove_handle($mh, $curl_array[$i]);
        }
        curl_multi_close($mh);
        return $res;
    }

    //单个抓取
    public static function single_grab_json_use_proxy($url) {
        if (empty($url)){return;}
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPPROXYTUNNEL => 1,
            CURLOPT_PROXY => '127.0.0.1:8087',
        );
        curl_setopt_array($ch, $options);
        $tmp_result = curl_exec($ch);
        $res = json_decode($tmp_result, true);
        curl_close($ch);
        return $res;
    }

    //多线程抓取
    public static function multiple_grab_json_use_proxy($nodes) {
        if (empty($nodes))
            return;

        $mh = curl_multi_init();
        $curl_array = array();
        foreach ($nodes as $i => $url) {
            $curl_array[$i] = curl_init($url);
            $options = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPPROXYTUNNEL => 1,
                CURLOPT_PROXY => '127.0.0.1:8087',
            );
            curl_setopt_array($curl_array[$i], $options);
            curl_multi_add_handle($mh, $curl_array[$i]);
        }
        $running = NULL;
        do {
            usleep(10000);
            curl_multi_exec($mh, $running);
        } while ($running > 0);

        $res = array();
        foreach ($nodes as $i => $url) {
            $tmp_result = curl_multi_getcontent($curl_array[$i]);
            $res[$url] = json_decode($tmp_result, true);
        }

        foreach ($nodes as $i => $url) {
            curl_multi_remove_handle($mh, $curl_array[$i]);
        }

        curl_multi_close($mh);
        return $res;
    }

}
