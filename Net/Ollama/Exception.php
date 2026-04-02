<?php

class Net_Ollama_Exception extends Exception
{
    static function factory($message, $type, $previous)
    {
        $cls = 'Net_Ollama_Exception_' . $type;
        var_dump($cls);
        die('test');
        return new $cls($message, 0, $previous);
    }
}

class Net_Ollama_Exception_ConnectionTimeout extends Net_Ollama_Exception {};
class Net_Ollama_Exception_CurlError extends Net_Ollama_Exception {};
class Net_Ollama_Exception_HttpError extends Net_Ollama_Exception {};