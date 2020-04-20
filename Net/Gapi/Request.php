<?php
/**
 * Google Analytics API request
 *
 */
class Services_Gapi_Request
{
    const http_interface = 'auto'; //'auto': autodetect, 'curl' or 'fopen'
    //const interface_name = gapi::interface_name;

    private $url = null;

    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * Return the URL to be requested, optionally adding GET variables
     *
     * @param Array $get_variables
     * @return String
     */
    public function getUrl($get_variables=null)
    {
        if (is_array($get_variables)) {
            $get_variables = '?' . str_replace('&amp;', '&', urldecode(http_build_query($get_variables, '', '&')));
        } else {
            $get_variables = null;
        }

        return $this->url . $get_variables;
    }

    /**
     * Perform http POST request
     * 
     *
     * @param Array $get_variables
     * @param Array $post_variables
     * @param Array $headers
     */
    public function post($get_variables=null, $post_variables=null, $headers=null)
    {
        return $this->request($get_variables, $post_variables, $headers);
    }

    /**
     * Perform http GET request
     * 
     *
     * @param Array $get_variables
     * @param Array $headers
     */
    public function get($get_variables=null, $headers=null)
    {
        return $this->request($get_variables, null, $headers);
    }

    /**
     * Perform http request
     * 
     *
     * @param Array $get_variables
     * @param Array $post_variables
     * @param Array $headers
     */
    public function request($get_variables=null, $post_variables=null, $headers=null)
    {
        $interface = self::http_interface;

        if (self::http_interface == 'auto')
            $interface = function_exists('curl_exec') ? 'curl' : 'fopen';

        switch ($interface) {
            case 'curl':
                return $this->curlRequest($get_variables, $post_variables, $headers);
            case 'fopen':
                return $this->fopenRequest($get_variables, $post_variables, $headers);
            default:
                throw new Exception('Invalid http interface defined. No such interface "' . self::http_interface . '"');
        }
    }

    /**
     * HTTP request using PHP CURL functions
     * Requires curl library installed and configured for PHP
     * 
     * @param Array $get_variables
     * @param Array $post_variables
     * @param Array $headers
     */
    private function curlRequest($get_variables=null, $post_variables=null, $headers=null)
    {
        $ch = curl_init();

        if (is_array($get_variables)) {
            $get_variables = '?' . str_replace('&amp;', '&', urldecode(http_build_query($get_variables, '', '&')));
        } else {
            $get_variables = null;
        }

        curl_setopt($ch, CURLOPT_URL, $this->url . $get_variables);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //CURL doesn't like google's cert

        if (is_array($post_variables)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_variables, '', '&'));
        }

        if (is_array($headers)) {
            $string_headers = array();
            foreach ($headers as $key => $value) {
                $string_headers[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $string_headers);
        }

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return array('body' => $response, 'code' => $code);
    }

    /**
     * HTTP request using native PHP fopen function
     * Requires PHP openSSL
     *
     * @param Array $get_variables
     * @param Array $post_variables
     * @param Array $headers
     */
    private function fopenRequest($get_variables=null, $post_variables=null, $headers=null)
    {
        $http_options = array('method'=>'GET', 'timeout'=>3);

        $string_headers = '';
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                $string_headers .= "$key: $value\r\n";
            }
        }

        if (is_array($get_variables)) {
            $get_variables = '?' . str_replace('&amp;', '&', urldecode(http_build_query($get_variables, '', '&')));
        }
        else {
            $get_variables = null;
        }

        if (is_array($post_variables)) {
            $post_variables = str_replace('&amp;', '&', urldecode(http_build_query($post_variables, '', '&')));
            $http_options['method'] = 'POST';
            $string_headers = "Content-type: application/x-www-form-urlencoded\r\n" . "Content-Length: " . strlen($post_variables) . "\r\n" . $string_headers;
            $http_options['header'] = $string_headers;
            $http_options['content'] = $post_variables;
        } else {
            $post_variables = '';
            $http_options['header'] = $string_headers;
        }

        $context = stream_context_create(array('http'=>$http_options));
        $response = @file_get_contents($this->url . $get_variables, null, $context);

        return array('body'=>$response!==false?$response:'Request failed, consider using php5-curl module for more information.', 'code'=>$response!==false?'200':'400');
    }
}