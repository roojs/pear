<?php

class Net_Ollama_Exception extends Exception
{
    function __construct($options = array()) 
    {
        foreach($options as $k => $v) {
            if(property_exists($this, $k)) {
                $this->{$k} = $v;
            }
        }
    }
}