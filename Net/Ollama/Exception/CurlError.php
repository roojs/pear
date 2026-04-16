<?php

require_once 'Net/Ollama/Exception.php';

class Net_Ollama_Exception_CurlError extends Net_Ollama_Exception
{
    var $curlError = '';

    function buildMessage() : string {
        return "cURL error: {$this->curlError}";
    }
}