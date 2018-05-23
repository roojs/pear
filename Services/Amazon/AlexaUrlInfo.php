<?php

class Services_Amazon_AlexaUrlInfo
{
    var $config = array(
        'accessKeyId' => '',
        'secretAccessKey' => '',
        'site' => ''
    );
    
    function __construct($config)
    {
        foreach($this->config as $k => $v) {
            if (isset($config[$k])) {
               $this->config[$k] = $config[$k];
            }
        }
        
        
        
    }
   
}

