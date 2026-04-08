<?php

require_once 'Net/Ollama/Exception.php';

class Net_Ollama_Exception_ConnectionTimeout extends Net_Ollama_Exception
{
    var $connectionTimeout = 0;
    function __construct($options = array()) 
    {
        $message = "Failed to connect";
        if(!empty($options['connectionTimeout'])) {
            $this->connectionTimeout = $options['connectionTimeout'];
            $message .= " within {$this->connectionTimeout} seconds";
        }

        parent::__construct($message);
    }
}