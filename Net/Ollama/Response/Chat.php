<?php

class_exists('Net_Ollama_Response') || require_once 'Net/Ollama/Response.php';

class Net_Ollama_Response_Chat extends Net_Ollama_Response {
    
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
     * @var object|string The model's generated message (for chat) or response text (for generate)
     */
    var $message;
    /**
     * @var string The role of the message (flattened from message[role])
     */
    var $role;
    /**
     * @var string The content of the message (flattened from message[content])
     */
    var $content = '';
    /**
     * @var string The model's generated text response (for generate endpoint)
     */
    var $response;
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
        if (isset($data['message'])) { // message only comes on api - not when we rebuild the object.
            $this->role = $data['message']['role'];
            $this->content = $data['message']['content'];
        }

        var_dump("MESSAGE");
        var_dump($data['message']);
        // Store messages for reply functionality
        $this->messages = !empty($data['messages']) ? $data['messages'] : array();
    }
    
    /**
     * Add a chunk to the stream response
     * Returns array with 'text' (new text content) and 'is_thinking' (boolean)
     */
    function addChunk($chunk)
    {
        //$this->oai->debug("Adding Chunk", $chunk);
        foreach ($chunk as $key => $value) {
            if ($key === 'message') {
                continue; // Skip message - handled separately for content accumulation
            }
            $this->$key = $value;
        }
        
 
        
        // Handle message content (regular content)
        if (!isset($chunk['message']['thinking'])) {
            
            $this->content .= $chunk['message']['content'];
            $this->is_thinking = false;
            return $chunk['message']['content'];
        }
        
        $this->thinking .= $chunk['message']['thinking'];
        $this->is_thinking = true;
        return $chunk['message']['thinking'];
         
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

