<?php

class Services_Amazon_AlexaUrlInfo
{
    var $config = array(
        'accessKeyId' => '',
        'secretAccessKey' => '',
        'site' => ''
    );
    
    var $amzDate = false;
    
    var $dateStamp = false;
    
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
   
}

