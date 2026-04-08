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