<?php

require_once 'Net/Ollama/Exception.php';

class Net_Ollama_Exception_ConnectionTimeout extends Net_Ollama_Exception
{
    var $connectionTimeout = 0;

    function buildMessage() : string {
        return "Failed to connect within {$this->connectionTimeout} seconds";
    }
}