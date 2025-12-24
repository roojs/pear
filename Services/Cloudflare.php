<?php

class Services_Cloudflare {
    
    
    var $baseURL;
    var $apiToken;
    
    /**
     * Constructor - sets properties from config array
     * Subclasses should call parent::__construct() and then set baseURL
     * 
     * @param array $cfg Configuration array
     */
    
    function __construct($cfg)
    {
        // no error checking - should result in warnings if done wrong...
        foreach($cfg as $k=>$v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }
    
    /**
     * Make HTTP request to Cloudflare API
     * 
     * @param string $method HTTP method (GET, POST, PATCH, DELETE)
     * @param string $param URL parameter/path
     * @param array $data Data to send (for POST/PATCH)
     * @return mixed API response or PEAR_Error on failure
     */
    
    function request($method, $param, $data = array()) 
    {
         // Headers for API requests
        $headers = array(
            "Authorization: Bearer {$this->apiToken}",
            "Content-Type: application/json"
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseURL . $param);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        switch($method) {
            
            case 'GET':
                break;
           
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            
            case 'POST' :
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                $param .= " / " . json_encode($data);
                break;
            
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                $param .= " / " . json_encode($data);
                break;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        
        // Check for curl errors
        if ($response === false) {
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);
            return $this->raiseError("Curl error: $method : $param - Error #{$curlErrno}: {$curlError}");
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 ) {
            $ret = json_decode($response);
            
            
            
            if (!$ret->success) {
                return $this->raiseError("Failed : $method : $param returned {$httpCode} - ". json_encode($ret->errors));
            }
            if (isset($ret->result_info)) {
                return $ret;
            }
            return $ret->result;
        }
        return $this->raiseError("Failed : $method : $param returned {$httpCode} - {$response}");
        
    }
    
    /**
     * Raise a PEAR error
     * 
     * @param string $message Error message
     * @return PEAR_Error
     */
    
    function raiseError($message = null)
    {
        require_once 'PEAR.php';
        $p = new PEAR();
        return $p->raiseError($message, null, PEAR_ERROR_RETURN);
    }
}


