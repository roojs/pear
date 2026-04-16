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
    /**
     * @var int VRAM size in bytes (ps endpoint)
     */
    var $size_vram;
    /**
     * @var int Time spent generating the response in nanoseconds (ps endpoint)
     */
    var $total_duration;
    /**
     * @var int Time spent loading the model in nanoseconds (ps endpoint)
     */
    var $load_duration;
    /**
     * @var int Number of input tokens in the prompt (ps endpoint)
     */
    var $prompt_eval_count;
    /**
     * @var int Time spent evaluating the prompt in nanoseconds (ps endpoint)
     */
    var $prompt_eval_duration;
    /**
     * @var int Number of output tokens generated (ps endpoint)
     */
    var $eval_count;
    /**
     * @var int Time spent generating tokens in nanoseconds (ps endpoint)
     */
    var $eval_duration;
    /**
     * @var string Model identifier (from ps endpoint)
     */
    var $model;
    /**
     * @var string ISO 8601 timestamp when model expires (ps endpoint)
     */
    var $expires_at;
    /**
     * @var int Context length of the model (ps endpoint)
     */
    var $context_length;

    var $remote_model;

    var $remote_host;
    
    function __construct($oai, $data)
    {
        parent::__construct($oai, $data);
    }
}

