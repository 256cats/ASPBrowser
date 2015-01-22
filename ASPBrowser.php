<?php
include_once __DIR__.'/simple_html_dom.php';
define('COOKIE_FILE', __DIR__.'/cookie.txt');
@unlink(COOKIE_FILE); //clear cookies before we start

define('CURL_LOG_FILE', __DIR__.'/request.txt');
@unlink(CURL_LOG_FILE);//clear curl log
class ASPBrowser {
    public $exclude = array();
    public $lastUrl = '';
    public $dom = false;
	
    /**Get simplehtmldom object from url
     * @param $url
     * @param $post
     * @return bool|simple_html_dom
     */
    public function getDom($url, $post = false) {
        $f = fopen(CURL_LOG_FILE, 'a+'); // curl session log file
        if($this->lastUrl) $header[] = "Referer: {$this->lastUrl}";
        $curlOptions = array(
            CURLOPT_ENCODING => 'gzip,deflate',
            CURLOPT_AUTOREFERER => 1,
            CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
            CURLOPT_TIMEOUT => 120, // timeout on response
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 9,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36",
            CURLOPT_COOKIEFILE => COOKIE_FILE,
            CURLOPT_COOKIEJAR => COOKIE_FILE,
            CURLOPT_STDERR => $f, // log session
            CURLOPT_VERBOSE => true,
        );
        if($post) { // add post options
            $curlOptions[CURLOPT_POSTFIELDS] = $post;
            $curlOptions[CURLOPT_POST] = true;
        }

        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);
        $data = curl_exec($curl);
        $this->lastUrl = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL); // get url we've been redirected to
        curl_close($curl);

        if($this->dom) {
            $this->dom->clear();
            $this->dom = false;
        }
        $dom = $this->dom = str_get_html($data);

        fwrite($f, "{$post}\n\n");
        fwrite($f, "-----------------------------------------------------------\n\n");
        fclose($f);

        return $dom;
    }

    /**Create parameters for POST request to ASP
     * @param $dom
     * @param array $params
     * @return array|string
     */
    function createASPPostParams($dom, array $params) {
        $postData = $dom->find('input,select,textarea');
        $postFields = array();
        foreach($postData as $d) {
            $name = $d->name;
            if(trim($name) == '' || in_array($name, $this->exclude)) continue;
            $value = isset($params[$name]) ? $params[$name] : $d->value;
            $postFields[] = rawurlencode($name).'='.rawurlencode($value);
        }
        $postFields = implode('&', $postFields);
        return $postFields;
    }

    /**Do POST request to url
     * @param $url
     * @param array $params
     * @return bool|simple_html_dom
     */
    function doPostRequest($url, array $params) {
        $post = $this->createASPPostParams($this->dom, $params);
        return $this->getDom($url, $post);
    }

    /**Do 'post back' request
     * @param $url
     * @param $eventTarget
     * @param string $eventArgument
     * @return bool|simple_html_dom
     */
    function doPostBack($url, $eventTarget, $eventArgument = '') {
        return $this->doPostRequest($url, array(
            '__EVENTTARGET' => $eventTarget,
            '__EVENTARGUMENT' => $eventArgument
        ));
    }

    /**Do GET request
     * @param $url
     * @return bool|simple_html_dom
     */
    function doGetRequest($url) {
        return $this->getDom($url);
    }

}


