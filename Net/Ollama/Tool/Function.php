<?php

class Net_Ollama_Tool_Function {
    var $name = '';
    var $description = '';
    var $parameters = array();
    
    function __construct($options = array())
    {
        // Universal constructor - accepts array or object
        // Populate properties from options
        foreach ((array)$options as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }
    
}

