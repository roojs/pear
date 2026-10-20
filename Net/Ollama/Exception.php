<?php

abstract class Net_Ollama_Exception extends Exception
{
    function __construct($options = array()) 
    {
        foreach((array)$options as $k => $v) {
            if(property_exists($this, $k)) {
                $this->{$k} = $v;
            }
        }

        parent::__construct($this->buildMessage());
    }

    abstract function buildMessage() : string;
}

class Net_Ollama_Exception_StaleTimeout extends Net_Ollama_Exception
{
    var $seconds = 0;

    function buildMessage() : string {
        return "No response from provider for {$this->seconds} seconds";
    }
}

class Net_Ollama_Exception_ConnectionTimeout extends Net_Ollama_Exception
{
    var $connectionTimeout = 0;

    function buildMessage() : string {
        return "Failed to connect within {$this->connectionTimeout} seconds";
    }
}

class Net_Ollama_Exception_CurlError extends Net_Ollama_Exception
{
    var $curlError = '';

    function buildMessage() : string {
        return "cURL error: {$this->curlError}";
    }
}

class Net_Ollama_Exception_HttpError extends Net_Ollama_Exception
{
    var $httpCode = 0;

    function buildMessage() : string {
        return "HTTP error: {$this->httpCode}";
    }
}
