<?php

class_exists('Net_Ollama_Response') || require_once 'Net/Ollama/Response.php';

class Net_Ollama_Response_Generate extends Net_Ollama_Response {
    
    var $call;
    /**
     * @var string Model name
     */
    var $model;
    /**
     * @var string ISO 8601 timestamp of response creation
     */
    var $created_at;
    /**
     * @var string The model's generated text response
     */
    var $response = '';
    /**
     * @var string The model's generated thinking output
     */
    var $thinking = '';
    /**
     * @var bool Indicates if current text is thinking content
     */
    var $is_thinking;
    /**
     * @var bool Indicates whether generation has finished
     */
    var $done;
    /**
     * @var string Reason the generation stopped
     */
    var $done_reason;
    /**
     * @var int Time spent generating the response in nanoseconds
     */
    var $total_duration;
    /**
     * @var int Time spent loading the model in nanoseconds
     */
    var $load_duration;
    /**
     * @var int Number of input tokens in the prompt
     */
    var $prompt_eval_count;
    /**
     * @var int Time spent evaluating the prompt in nanoseconds
     */
    var $prompt_eval_duration;
    /**
     * @var int Number of output tokens generated in the response
     */
    var $eval_count;
    /**
     * @var int Time spent generating tokens in nanoseconds
     */
    var $eval_duration;

    var $context;
    
    function __construct($oai, $data)
    {
        parent::__construct($oai, $data);
        // Populate response field if present
        if (isset($data['response'])) {
            $this->response = $data['response'];
        }
    }
    
    /**
     * Add a chunk to the stream response
     * Returns the new text content (response or thinking)
     */
    function addChunk($chunk)
    {
        foreach ($chunk as $key => $value) {
            if ($key === 'response' || $key === 'thinking') {
                continue; // Skip response/thinking - handled separately for content accumulation
            }
            $this->$key = $value;
        }
        
        // Handle thinking content
        if (isset($chunk['thinking']) && !empty($chunk['thinking'])) {
            $this->thinking .= $chunk['thinking'];
            $this->is_thinking = true;
            return $chunk['thinking'];
        }
        
        // Handle regular response content
        if (isset($chunk['response'])) {
            $this->response .= $chunk['response'];
            $this->is_thinking = false;
            return $chunk['response'];
        }
        
        return '';
    }
}

