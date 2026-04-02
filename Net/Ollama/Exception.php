<?php

class Net_Ollama_Exception extends Exception
{
    static function factory($message, $type, $previous)
    {
        $cls = 'Net_Ollama_Exception_' . $type;
        return new $cls($message, 0, $previous);
    }
}