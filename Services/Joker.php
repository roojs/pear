<?php

/** based on the Joker.com API example code */

require_once 'PEAR/Exception.php';

class Services_Joker {
    
    var $sessid = false;
    
    var $dmapi_url = '';
    var $outgoing_network_interface = false;
    var $timeout = 30;
    var $connect_timeout = 30;


    var $account_balance = 0; // returned data?
    
    function __construct($cfg)
    {
        
        
    }
    
    function execute($request, $params)
    {
        
        // if we are not logged in, then we should not be able to do a request..
        // unless it's a login/query-request-list
        //if ($this->is_request_available($request)) {
        
        $http_query =  "/request/" . $request . "?" . $this->paramsToString($params,$this->sessid);
        //$log_http_query = "/request/" . $request . "?" . $this->paramsToString($params,$this->sessid,true);
   
       
            //send the request
        $raw_res = $this->query_host($http_query, true);
        $temp_arr = @explode("\r\n\r\n", $raw_res, 2); // headers + body...
        if (!is_array($temp_arr) || 2 != count($temp_arr)) {
            throw new PEAR_Exception(__CLASS__.'::'.__FUNCTION__ .': returned '. curl_error($ch));
        }
        
        $response = $this->parseResponse($temp_arr[1]);
        $response["http_header"] = $temp_arr[0];
                //get account balance
        if (isset($response["response_header"]["account-balance"])) {
            $this->account_balance = $response["response_header"]["account-balance"];
        }
        
        $success = true;
        if (!isset($sessdata["response_header"]["status-code"]) || $sessdata["response_header"]["status-code"] != "0") {
            $success = false;
        }
        
        $http_code  = "0";
        preg_match("/^HTTP\/1.[0-1]\b ([0-9]{3}) /i", $response["http_header"], $matches);
        if (is_array($matches) && $matches[1]) {
             $http_code = $matches[1];
        } 
        
        if ($http_code[0] != '2') {
            $success = false;
        }
        
        //status
        if ($success) {
            return $response;
        }
        
            
        if ("401" == $http_code) {
            //kills web session
            //session_destroy();
            //deletes session auth-id
            $this->sessid = "";
            // save session?
        }            
        return '';
        
    }
    
    
    
    function sendQuery($params = "", $get_header = false)
    {        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->dmapi_url.$params);
        
        if (preg_match("/^https:\/\//i", $this->dmapi_url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }
        
        if ($this->outgoing_network_interface) {
            curl_setopt($ch, CURLOPT_INTERFACE, $this->outgoing_network_interface);
        }
        
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        
        curl_setopt($ch, CURLOPT_HEADER, $get_header ? 1: 0);
        
        //if ($this->config["curlexec_proceed"]) {
            $result = curl_exec($ch);
        //}

        if (curl_errno($ch)) {
            throw new PEAR_Exception(__CLASS__.'::'.__FUNCTION__ .': returned '. curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }
    
    
    function paramsToString($formdata, $sessid, $build_log_query = false, $numeric_prefix = null)
    {
        
        if (!is_array($formdata)) {
            throw new PEAR_Exception(__CLASS__.'::'.__FUNCTION__ .': formdata not an array');
        }
        
        if ($sessid) { // && $sessid != $this->config["no_content"]) {
            $formdata["auth-sid"] = $sessid;
        }
 

        //The IP of the user should be always present in the requests
        $formdata["client-ip"] = $_SERVER["REMOTE_ADDR"];

        //Some values should not be present in the logs -- this is configurable ?? why??
        /*if ($build_log_query) {
            foreach ($this->hide_field_values as $value)
            {
                if (isset($formdata[$value])) {
                    $formdata[$value] = $this->hide_value_text;
                }
            }
        }
        */

        // If the array is empty, return null
        if (empty($formdata)) {
             throw new PEAR_Exception(__CLASS__.'::'.__FUNCTION__ .': formdata empty');
        }

        // Start building the query
        $tmp = array ();
        foreach ($formdata as $key => $val)
        {            
            if (is_integer($key) && $numeric_prefix != null) {
                $key = $numeric_prefix . $key;
            }

            if (is_scalar($val) && (trim($val) != "")) {                
                //if (trim(strtolower($val)) == $this->config["empty_field_value"]) {                    
                //    $val = "";
                //}
                //if (!$build_log_query) {
                    $tmp_val = urlencode($key).'='.urlencode(trim($val));
                //} else {
                //    $tmp_val = $key.'='.trim($val);
                //}
                $tmp[] = $tmp_val;
                continue;
            }
        }
        return = implode('&', $tmp);
        
    }
    
}
