<?php

require_once 'Net/Ollama/Exception.php';

class Net_Ollama_Exception_ConnectionTimeout extends Net_Ollama_Exception
{
    var $connectionTimeout = 0;
    static function factory($options = array())
    {
        if(!empty($options['connectionTimeout'])) {
            $this->connectionTimeout = $options['connectionTimeout'];
        }
    }
}