<?php

class Services_Amazon_AlexaUrlInfo
{
    var $config = array(
        'accessKeyId'       => '',
        'secretAccessKey'   => '',
        'site'              => '',
        'ResponseGroupName' => 'UsageStats', // refer to https://docs.aws.amazon.com/AlexaWebInfoService/latest/ApiReference_UrlInfoAction.html
        'NumReturn'         => 10
    );
    
    var $ServiceHost = 'awis.amazonaws.com';
    var $ServiceEndpoint = 'awis.us-west-1.amazonaws.com';
    var $StartNum = 1;
    var $SigVersion = 2;
    var $HashAlgorithm = 'HmacSHA256';
    var $ServiceURI = "/api";
    var $ServiceRegion = "us-west-1";
    var $ServiceName = "awis";
    
    var $amzDate = false;
    
    var $dateStamp = false;
    
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
            'Count'             => $this->config['NumReturn'],
            'ResponseGroup'     => $this->config['ResponseGroupName'],
            'Start'             => $this->StartNum,
            'Url'               => $this->config['site']
        );
        
        ksort($params);
        
        $keyvalue = array();
        
        foreach($params as $k => $v) {
            $keyvalue[] = $k . '=' . rawurlencode($v);
        }
        
        return implode('&',$keyvalue);
    }
    
    function buildHeaders($list) 
    {
        $params = array(
            'host'            => $this->ServiceEndpoint,
            'x-amz-date'      => $this->amzDate
        );
        
        ksort($params);
        
        $keyvalue = array();
        
        foreach($params as $k => $v) {
            if ($list){
                $keyvalue[] = $k . ':' . $v;
            } else {
              $keyvalue[] = $k;
            }
        }
        
        return ($list) ? implode("\n",$keyvalue) . "\n" : implode(';',$keyvalue) ;
    }
   
}

