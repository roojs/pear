<?php

class_exists('Net_Ollama_Response') || require_once 'Net/Ollama/Response.php';

class Net_Ollama_Response_Model extends Net_Ollama_Response {
    /**
     * @var string Model name (e.g., "gemma3")
     */
    var $name;
    /**
     * @var string ISO 8601 timestamp of last modification
     */
    var $modified_at;
    /**
     * @var int Model size in bytes
     */
    var $size;
    /**
     * @var string Model hash digest
     */
    var $digest;
    /**
     * @var object Model details including format, family, parameter_size, quantization_level
     */
    var $details;
    
    function __construct($oai, $data)
    {
        parent::__construct($oai, $data);
    }
}

