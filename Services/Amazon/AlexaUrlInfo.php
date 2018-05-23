<?php

class Services_Amazon_AlexaUrlInfo
{
    protected static $ActionName        = 'UrlInfo';
    protected static $ResponseGroupName = 'Rank,LinksInCount';
    protected static $ServiceHost      = 'awis.amazonaws.com';
    protected static $ServiceEndpoint  = 'awis.us-west-1.amazonaws.com';
    protected static $NumReturn         = 10;
    protected static $StartNum          = 1;
    protected static $SigVersion        = '2';
    protected static $HashAlgorithm     = 'HmacSHA256';
    protected static $ServiceURI = "/api";
    protected static $ServiceRegion = "us-west-1";
    protected static $ServiceName = "awis";
    
    var $amzDate = false;
    var $dateStamp = false;
    
    function __construct($accessKeyId, $secretAccessKey, $site)
    {
        $this->accessKeyId = $accessKeyId;
        $this->secretAccessKey = $secretAccessKey;
        $this->site = $site;
        
        $now = time();
        $this->amzDate = gmdate("Ymd\THis\Z", $now);
        $this->dateStamp = gmdate("Ymd", $now);
    }
    
    public function getUrlInfo() 
    {
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
    
    function getSignatureKey() 
    {
        $kSecret = 'AWS4' . $this->config['secretAccessKey'];
        $kDate = $this->sign($kSecret, $this->dateStamp);
        $kRegion = $this->sign($kDate, $this->ServiceRegion);
        $kService = $this->sign($kRegion, $this->ServiceName);
        $kSigning = $this->sign($kService, 'aws4_request');
        return $kSigning;
    }
   
}

