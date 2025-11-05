<?php

class_exists('Net_Ollama_Response') || require_once 'Net/Ollama/Response.php';

class Net_Ollama_Response_Chat extends Net_Ollama_Response {
    
    protected $call;
    /**
     * @var string Model name
     */
    var $model;
    /**
     * @var string ISO 8601 timestamp of response creation
     */
    var $created_at;
    /**
     * @var object|string The model's generated message (for chat) or response text (for generate)
     */
    var $message;
    /**
     * @var string The model's generated text response (for generate endpoint)
     */
    var $response;
    /**
     * @var string The model's generated thinking output
     */
    var $thinking;
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
    /**
     * @var array Chat history as an array of message objects (for chat endpoint)
     */
    var $messages; // Conversation history for reply()
    /**
     * @var array Array of choice objects (OpenAI compatibility)
     */
    var $choices;
    /**
     * @var object Usage statistics (OpenAI compatibility)
     */
    var $usage;
    
    function __construct($oai, $data)
    {
        parent::__construct($oai, $data);
        // Store messages for reply functionality
        $this->messages = isset($data['messages']) ? $data['messages'] : array();
    }
    
    function reply($message)
    {
        // Start with conversation history
        $messages = isset($this->messages) ? $this->messages : array();
        
        // Add assistant response from this object (if available)
        if (!empty($this->choices) && isset($this->choices[0]['message'])) {
            $messages[] = $this->choices[0]['message'];
        }
        
        // Add new user message
        $messages[] = array('role' => 'user', 'content' => $message);
        
        // Send new request with conversation history
        return $this->oai->chat(array(
            'model' => $this->model,
            'messages' => $messages
        ));
    }
}

