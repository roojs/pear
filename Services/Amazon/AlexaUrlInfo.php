<?php

class Services_Amazon_AlexaUrlInfo
{
    var $config = array(
        'accessKeyId'       => '',
        'secretAccessKey'   => '',
        'site'              => ''
    );
    
    var $amzDate = false;
    
    var $dateStamp = false;
    
    var $numberReturn = 10;
    
    var $action = false;
    
    function __construct($config)
    {
        foreach($this->config as $k => $v) {
            if (isset($config[$k])) {
               $this->config[$k] = $config[$k];
            }
        }
        
        $now = time();
        $this->amzDate = gmdate("Ymd\THis\Z", $now);
        $this->dateStamp = gmdate("Ymd", $now);
        
    }
    
    function getUrlInfo() 
    {
        $this->action = 'UrlInfo';
        
        if(
                empty($this->config['accessKeyId']) ||
                empty($this->config['secretAccessKey']) ||
                empty($this->config['site'])
        ) {
            throw new exception("Missing Access Information");
        }
        
        $canonicalQuery = $this->buildQueryParams();
        
        $canonicalHeaders =  $this->buildHeaders(true);
        $signedHeaders = $this->buildHeaders(false);
        $payloadHash = hash('sha256', "");
        $canonicalRequest = "GET" . "\n" . self::$ServiceURI . "\n" . $canonicalQuery . "\n" . $canonicalHeaders . "\n" . $signedHeaders . "\n" . $payloadHash;
        $algorithm = "AWS4-HMAC-SHA256";
        $credentialScope = $this->dateStamp . "/" . self::$ServiceRegion . "/" . self::$ServiceName . "/" . "aws4_request";
        $stringToSign = $algorithm . "\n" .  $this->amzDate . "\n" .  $credentialScope . "\n" .  hash('sha256', $canonicalRequest);
        $signingKey = $this->getSignatureKey();
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);
        $authorizationHeader = $algorithm . ' ' . 'Credential=' . $this->accessKeyId . '/' . $credentialScope . ', ' .  'SignedHeaders=' . $signedHeaders . ', ' . 'Signature=' . $signature;

        $url = 'https://' . self::$ServiceHost . self::$ServiceURI . '?' . $canonicalQuery;
        $ret = self::makeRequest($url, $authorizationHeader);
        echo "\nResults for " . $this->site .":\n\n";
        echo $ret;
        self::parseResponse($ret);
    }
    
    function buildQueryParams() 
    {
        $params = array(
            'Action'            => $this->action,
            'Count'             => self::$NumReturn,
            'ResponseGroup'     => self::$ResponseGroupName,
            'Start'             => self::$StartNum,
            'Url'               => $this->site
        );
        ksort($params);
        $keyvalue = array();
        foreach($params as $k => $v) {
            $keyvalue[] = $k . '=' . rawurlencode($v);
        }
        return implode('&',$keyvalue);
    }
   
}

