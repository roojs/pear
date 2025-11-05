<?php

class Net_Ollama_Tool {
    var $type = 'function';
    var $function;
    
    function __construct($options = array())
    {
        // Universal constructor - accepts array or object
        // Populate properties from options
        foreach ((array)$options as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
        
        // Convert function array/object to Net_Ollama_Tool_Function instance
        
        class_exists('Net_Ollama_Tool_Function') || require_once 'Net/Ollama/Tool/Function.php';
        $this->function = new Net_Ollama_Tool_Function($this->function);
    
    }
}

