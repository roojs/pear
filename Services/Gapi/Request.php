<?php
/**
 * Google Analytics API request
 *
 */
class Services_Gapi_Request
{

    var $url = null;
    var $debug;

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
    public function getUrl($get_variables='')
    {
        if (is_array($get_variables)) {
            $get_variables = '?' . str_replace('&amp;', '&', urldecode(http_build_query($get_variables, '', '&')));
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
    public function post($get_variables=null, $post_variables=null, $headers=array())
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
    public function get($get_variables='', $headers=array())
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
    public function request($get_variables=null, $post_variables=null, $headers=array())
    {
       
        $ch = curl_init();

        if (is_array($get_variables)) {
            $get_variables = '?' . str_replace('&amp;', '&', urldecode(http_build_query($get_variables, '', '&')));
        } else {
            $get_variables = null;
        }
        $this->debug && print_R(array($this->url, $get_variables, $post_variables, $headers));
        curl_setopt($ch, CURLOPT_URL, $this->url . $get_variables);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //CURL doesn't like google's cert

        if (is_array($post_variables)) {
            curl_setopt($ch, CURLOPT_POST, true);
            if (preg_match('/token$/', $this->url)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_variables, '', '&'));
            } else {
                $headers["Content-type"] = "application/json";

                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_variables));
            }
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
        $this->debug &&  print_r("REPLY:");
        $this->debug &&  print_r($response);
        return array('body' => $response, 'code' => $code);
    }

   
}