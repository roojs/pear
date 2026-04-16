<?php

require_once 'Net/Ollama/Exception.php';

class Net_Ollama_Exception_HttpError extends Net_Ollama_Exception
{
    var $httpCode = 0;

    function buildMessage() : string {
        return "HTTP error: {$this->httpCode}";
    }
}