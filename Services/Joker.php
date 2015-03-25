<?php

/** based on the Joker.com API example code */

require_once 'PEAR/Exception.php';

class Services_Joker {
    
    var $username = '';
    var $password = '';
    var $sessid = false;
    var $public_ip = false;
    
    var $dmapi_url = 'https://dmapi.joker.com';
    var $outgoing_network_interface = false;
    var $timeout = 30;
    var $connect_timeout = 30;


    var $account_balance = 0; // returned data?
    
    function __construct($cfg=array())
    {
        foreach($cfg as $k=>$v) {
            $this->$k = $v;
        }
        // do nothing???
        // or should it login/load session etc...?
        
        
    }
    function __destruct()
    {
        // write the session id to $_SESSION???
        
    }
    
    /*---------- actual functions... ------*/
    
    
    function login()
    {
        // are we logged in
        if (!empty($this->sessid)) {
            return true;
        }
        
        $res = $this->execute('login', array(
            'username' => $this->username,
            'password' => $this->password
        ));
        return empty($this->sessid) ? $res : true;
    
    }
    
    function query_domain_list($pattern='*') // limit ?
    {
        $res = $this->login();
        //var_dump($res);exit;
        if ($res !== true) {
            return $res;
        }
        $res = $this->execute('query-domain-list', array(
            'pattern' => $pattern='*',
            "showstatus" => 1,
            "showgrants" => 1
        ));
        if (is_object($res)) {
            return $res;
        }
        $res = $this->parseResponseList($res);
        //print_r($res);
        return $res;
    }
    
    
    function dns_zone_get($domain)
    {
        $res = $this->login();
        //var_dump($res);exit;
        if ($res !== true) {
            return $res;
        }
        $res = $this->execute('dns-zone-get', array(
            'domain' => $domain
            
        ));
        if (is_object($res)) {
            return $res;
        }
        $res = $this->parseResponseList($res);
        //print_r($res);
        
        $keys = array(   'label', 'type' ,  'pri' , 'target', 'ttl'); //, 'valid-from' ,'valid-to',   'parameters');
        $ret = array();
        foreach($res as $r) {
            $rr = array();
            if (count($r) == 1) {
                // dyndns
                $vals = explode('=', $r[0]);
                $ret[] = array($vals[0] => $vals[1]);
                continue;
            }
            
            foreach($r as $i=>$v) {
                $rr[$keys[$i]] = $v;
            }
            $ret[] = $rr;
        }
        
       
        
        return $ret;
    }
    /**
     *@param string $domain the domain to change
     *@param string $dyndns empty string or user:pass
     *@param array $records array of records:
     *format:
     * array(
            array(
                'label' => www',
                'type' => 'A',
                'pri' => 0  // optional
                'target' => '192.168.0.1',
                'ttl' => '' // optional
                'valid-from' => '',// optional from now on..
                'valid-to' => '',
                'parameters' => ''
            )
     )
     *
     */
    
    function dns_zone_put($domain, $dyndns, $records)
    {
        $res = $this->login();
        //var_dump($res);exit;
        if ($res !== true) {
            return $res;
        }
        /// build the  records..
        
        
        //echo '<PRE>'; print_r($zone); 
        
        $zone = dns_zone_toString($dyndns,$records);
        
        $res = $this->execute('dns-zone-put', array(
            'domain' => $domain,
            'zone' => implode("\n", $zone)
            
        ));
        if (is_object($res)) {
            return $res;
        }
        $res = $this->parseResponseList($res);
        //print_r($res);
        return $res;
    }
    function dns_zone_toString($dyndns, $records)
    {
        $zone = array();
        $zone[] = '$dyndns=' . (empty($dyndns) || $dyndns == 'no::' ? 'no::' : ('yes:'.$dyndns));
        
        $keys = array(   'label', 'type' ,  'pri' , 'target', 'ttl'); //, 'valid-from' ,'valid-to',   'parameters');
        
        $defaults = array(
            'pri' => 0,
            'valid-from' => 0,
            'valid-to' => 0,
            'ttl' => 86400,
            'parameters' => '',
        );
        foreach($records as $r) {
            $row = array();
            if (isset($r['$dyndns'])) { // skip - if input is based on output of get...
                continue;
            }
            
            foreach($keys as $i=>$k) {
                if (!isset($r[$k]) && !isset($defaults[$k]))  {
                    return $this->raiseError("invalid record ". print_R($r,true));
                }
                if ($k == 'ttl' && empty($r[$k])) {
                    $r[$k] =  $defaults[$k] ;
                }
                $row[] = isset($r[$k]) ? $r[$k] : (isset($defaults[$k]) ? $defaults[$k] : '');
            }
            $zone[] = implode("\t", $row);
        }
        return implode("\n", $zone);
        
        
    }
    
    
    /*------------ handle the connections etc.. */
    
    /**
     *
     *@ returns array|PEAR_Error
     */
    
    function execute($request, $params)
    {
        
        // if we are not logged in, then we should not be able to do a request..
        // unless it's a login/query-request-list
        //if ($this->is_request_available($request)) {
        
        $parstr = $this->paramsToString($params,$this->sessid);
        if (is_object($parstr)) {
            return $parstr;
        }
        
         //$log_http_query = "/request/" . $request . "?" . $this->paramsToString($params,$this->sessid,true);
   
       
            //send the request
        $raw_res = $this->sendQuery( "/request/" . $request, $parstr, true);
        $temp_arr = @explode("\r\n\r\n", $raw_res, 2); // headers + body...
        if (!is_array($temp_arr) || 2 != count($temp_arr)) {
            return $this->raiseError(__CLASS__.'::'.__FUNCTION__ .': returned '. curl_error($ch));
        }
        
        $response = $this->parseResponse($temp_arr[1]);
        
        //var_dump($response);exit;
        
        $response["http_header"] = $temp_arr[0];
                //get account balance
        if (isset($response["response_header"]["account-balance"])) {
            $this->account_balance = $response["response_header"]["account-balance"];
        }
        
        $success = true;
        if (!isset($response["response_header"]["status-code"]) || $response["response_header"]["status-code"] != "0") {
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
        
        if (isset($response["response_header"]["auth-sid"]) && $response["response_header"]["auth-sid"]) {
            $this->sessid = $response["response_header"]["auth-sid"];
            
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
    function parseResponse($res)
    {
        $raw_arr = explode("\n\n", trim($res));
        $arr_elements = count($raw_arr);
        if ($arr_elements < 1) {
            return array();
        }
        if ($arr_elements < 2) {
            return array(
                "response_header" => $this->parseResponseHeaders($raw_arr[0])
            );
        }
        if ($arr_elements < 3) {
             return array(
                "response_header" => $this->parseResponseHeaders($raw_arr[0]),
                "response_body" => $raw_arr[1]
            );
        }
        $head = array_shift($raw_arr);
        $skip = array_shift($raw_arr);
        return array(
                "response_header" => $this->parseResponseHeaders($head),
                "response_body" => implode("\n\n",$raw_arr)
        );
         
    }
    function parseResponseList($response)
    {
        if (empty($response["response_body"])) {
            return '';
        }
        $text = trim($response["response_body"]);
        $columns = array();
        $separator = " ";
        if (!isset($response["response_header"]["columns"])) {
            return $this->parseResponseText($text);
        } 
        $columns = explode(",", $response["response_header"]["columns"]);
        
        if (isset($response["response_header"]["separator"])) {
            switch ($response["response_header"]["separator"]) {
                case "SPACE":
                    $separator = " ";
                    break;
                case "TAB":
                    $separator = "\t";
                    break;
            }
        }
        if (empty($text)) {
            return array();
        }
        
        $result = array();
        $raw_arr = explode("\n", $text);
        
        foreach ($raw_arr as $key => $value) {
            
            $temp_val = explode($separator, $value, count($columns));
            // padd...
            for ($i=count($temp_val);$i<count($columns);$i++) {
                $temp_val[] = "";
            }
            $result[$key] = array_combine($columns,$temp_val);

            
        }
        return $result;
    }
    function parseResponseText($text, $keyval = false, $limit = 0)
    {
        $text = trim($text);
        if (empty($text)) {
            return array();
        }
        $raw_arr = explode("\n", $text);
        $result = array();
        foreach ($raw_arr as $key => $value)
        {
            if (!$keyval) {
                if ($limit>0) {
                    $result[$key] = explode(" ",$value,$limit);
                } else {
                    $result[$key] = explode(" ",$value);
                }
                continue;
            }
            
            $temp_val = explode(" ", $value);
            $val1 = array_shift($temp_val);
            $result[$key] = array($val1,implode(" ",$temp_val));

        }
    
        return $result;
    }
    
    function parseResponseHeaders($header)
    {
        $raw_arr = explode("\n", trim($header));
        $result = array();
        
        foreach ($raw_arr as $key => $value) {
            $keyval = array();
            if (!preg_match("/^([^\s]+):\s*(.*)\s*$/", $value, $keyval)) {
                continue;
            }
            $keyval[1] = strtolower($keyval[1]);
            if (isset($arr[$keyval[1]])) {
                if (!is_array($arr[$keyval[1]])) {
                    $prev = $arr[$keyval[1]];
                    $arr[$keyval[1]] = array();
                    $arr[$keyval[1]][] = $prev;
                    $arr[$keyval[1]][] = $keyval[2];
                } else {
                    $arr[$keyval[1]][] = $keyval[2];
                }
            } else {
                if ($keyval[2] != "") {
                    $arr[$keyval[1]] = $keyval[2];
                } else {
                    $arr[$keyval[1]] = "";
                }
            }
            
        }
        return $arr;
    }
    
    function sendQuery($urlpart, $params = "", $get_header = false)
    {        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->dmapi_url.$urlpart);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        
        
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
            return $this->raiseError(__CLASS__.'::'.__FUNCTION__ .': returned '. curl_error($ch));
        }
        curl_close($ch);
        $this->debug("SEND: " . $params);
        $this->debug("RETURN: " . $result);
        return $result;
    }
    
    
    function paramsToString($formdata, $sessid, $build_log_query = false, $numeric_prefix = null)
    {
        
        if (!is_array($formdata)) {
            return $this->raiseError(__CLASS__.'::'.__FUNCTION__ .': formdata not an array');
        }
        
        if ($sessid) { // && $sessid != $this->config["no_content"]) {
            $formdata["auth-sid"] = $sessid;
        }
 

        //The IP of the user should be always present in the requests
        $formdata["client-ip"] = !empty($this->public_ip) ? $this->public_ip : $_SERVER["REMOTE_ADDR"];

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
            return $this->raiseError(__CLASS__.'::'.__FUNCTION__ .': formdata empty');
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
        return  implode('&', $tmp);
        
    }
    function debug($str)
    {
        if (empty($this->debug)) {
            return;
        }
        
        echo '<PRE>' . $str . '<PRE>';
        
        
    }
    function raiseError($message = null,
                         $code = null,
                         $mode = null,
                         $options = null,
                         $userinfo = null,
                         $error_class = null,
                         $skipmsg = false)
    {
        $this->debug("ERROR: $message");
        $p = new PEAR();
        return $p->raiseError($message,$code,$mode,$options,$userinfo,$error_class,$skipmsg);
    }
}
