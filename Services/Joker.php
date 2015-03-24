<?php

/** based on the Joker.com API example code */

class Services_Joker {
    
    var $sessid = false;
    
    function __construct($cfg)
    {
        
        
    }
    
    function execute($request, $params)
    {
        
        // if we are not logged in, then we should not be able to do a request..
        // unless it's a login/query-request-list
        //if ($this->is_request_available($request)) {
        
        $http_query =  "/request/" . $request . "?" . $this->paramsToString($params,$this->sessid);
        $log_http_query = "/request/" . $request . "?" . $this->paramsToString($params,$this->sessid,true);
   
       
            //send the request
        $raw_res = $this->query_host($this->config["dmapi_url"], $this->http_query, true);
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
    
}
