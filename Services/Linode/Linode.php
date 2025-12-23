<?php

class Services_Linode_Linode {
    
    var $apiToken;
    var $baseURL = "https://api.linode.com/v4";
    
    /**
     * Constructor - sets properties from config array
     * 
     * @param array|string $cfg Configuration array or API token string
     */
    
    function __construct($cfg)
    {
        if (is_string($cfg)) {
            // If passed a string, assume it's the API token
            $this->apiToken = $cfg;
            return;
        }
        
        // If passed an array, set properties from it
        foreach($cfg as $k=>$v) {
            if (!property_exists($this, $k)) {
                continue;
            }
            
            $this->$k = $v;
        }
    }
    
    /**
     * Make HTTP request to Linode API
     * 
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param string $path API path (relative to baseURL)
     * @param array $data Data to send (for POST/PUT/PATCH)
     * @return mixed API response array or false on failure
     */
    
    function request($method, $path, $data = null) 
    {
        $url = $this->baseURL . $path;
        
        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        }
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }
        
        if (in_array($method, ['PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        
        if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Linode API request failed: $method $path returned $httpCode - $response");
            return false;
        }
        
        return json_decode($response, true);
    }
}

