<?php

/** based on the Joker.com API example code */

class Services_Joker {
    
    var $sessid = false;
    
    var $dmapi_url = '';
    var $outgoing_network_interface = false;
    
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
        $raw_res = $this->query_host($this->dmapi_url, $http_query, true);
            $temp_arr = @explode("\r\n\r\n", $raw_res, 2);

            //split the response for further processing
            if (is_array($temp_arr) && 2 == count($temp_arr)) {
                $response = $this->parse_response($temp_arr[1]);
                $response["http_header"] = $temp_arr[0];
                //get account balance
                if (isset($response["response_header"]["account-balance"])) {
                    $_SESSION["auto_config"]["account_balance"] = $response["response_header"]["account-balance"];
                }
            } else {
                $this->log->req_status("e", "function execute_request(): Couldn't split the response into http header and response header/body\nRaw result:\n$raw_res");
                return false;
            }
            //status
            if ($this->http_srv_response($response["http_header"]) && $this->request_status($response)) {
                $this->log->req_status("i", "function execute_request(): Request was successful");
                $this->log->debug($request);
                $this->log->debug($response);
                return true;
            } else {
                $http_code = $this->get_http_code($response["http_header"]);
                if ("401" == $http_code) {
                    //kills web session
                    session_destroy();
                    //deletes session auth-id
                    $sessid = "";
                }            
            }
        } else {
            $this->log->req_status("e", "function execute_request(): Request $request is not supported in this version of DMAPI.");
        }
        return false;
        
        
    }
    
    function sendQuery($params = "", $get_header = false)
    {        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->dmapi_url.$params);
        
        if (preg_match("/^https:\/\//i", $this->dmapi_url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }
        
        if ($this->config["set_outgoing_network_interface"]) {
            curl_setopt($ch, CURLOPT_INTERFACE, $this->config["outgoing_network_interface"]);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->config["curlopt_connecttimeout"]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config["curlopt_timeout"]);        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($get_header) {
            curl_setopt($ch, CURLOPT_HEADER, 1);
        } else {
            curl_setopt($ch, CURLOPT_HEADER, 0);
        }
        
        if ($this->config["curlexec_proceed"]) {
            $result = curl_exec($ch);
        }

        if (curl_errno($ch)) {
            $this->log->req_status("e", "function query_host(): ".curl_error($ch));
        } else {
            $_SESSION["last_request_time"] = time();
            curl_close($ch);
        }       
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
