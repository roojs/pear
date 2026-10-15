<?php

require_once 'Net/Ollama/Exception.php';

class Net_Ollama_Exception_StaleTimeout extends Net_Ollama_Exception
{
    var $seconds = 0;

    function buildMessage() : string {
        return "No response from provider for {$this->seconds} seconds";
    }
}
