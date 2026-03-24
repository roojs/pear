<?php

class_exists('Net_Ollama_Response') || require_once 'Net/Ollama/Response.php';

class Net_Ollama_Response_Chat extends Net_Ollama_Response {
    
    var $call;
    /**
     * @var string Model name
     */
    var $model;
    /**
     * @var string OpenAI object type (e.g. chat.completion) when present
     */
    var $object;
    /**
     * @var int|null Unix creation time from OpenAI-compatible responses
     */
    var $created;
    /**
     * @var string ISO 8601 timestamp of response creation
     */
    var $created_at;
    /**
     * @var string|null OpenAI system_fingerprint when present
     */
    var $system_fingerprint;
    /**
     * @var object|string The model's generated message (for chat)
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
     * @var array Array of choice objects (OpenAI compatibility)
     */
    var $choices;
    /**
     * @var object Usage statistics (OpenAI compatibility)
     */
    var $usage;
    
    function __construct($oai, $data)
    {
        // OpenAI-compatible /v1/chat/completions (non-streaming): normalize to Ollama-style top-level fields.
        if (isset($data['choices'])) {
            if (isset($data['created'])) {
                $data['created_at'] = gmdate('Y-m-d\TH:i:s\Z', $data['created']);
            }
            if (isset($data['choices'][0]['message'])) {
                $data['message'] = $data['choices'][0]['message'];
                if (isset($data['message']['reasoning_content'])) {
                    $data['message']['thinking'] = $data['message']['reasoning_content'];
                }
            }
            if (isset($data['choices'][0]['finish_reason'])) {
                $data['done'] = true;
                $data['done_reason'] = $data['choices'][0]['finish_reason'];
            }
        }

        parent::__construct($oai, $data);
        if (isset($data['message'])) {
            $this->role = $data['message']['role'];
            $this->content = isset($data['message']['content']) ? $data['message']['content'] : '';
        }
    }
    
    /**
     * Add a chunk to the stream response
     * Returns array with 'text' (new text content) and 'is_thinking' (boolean)
     */
    function addChunk($chunk)
    {
        // OpenAI-compatible streaming chunk: choices[0].delta -> message, etc.
        if (isset($chunk['choices'])) {
            if (isset($chunk['created'])) {
                $chunk['created_at'] = gmdate('Y-m-d\TH:i:s\Z', $chunk['created']);
            }
            if (isset($chunk['choices'][0]['delta'])) {
                $chunk['message'] = $chunk['choices'][0]['delta'];
                if (isset($chunk['message']['reasoning_content'])) {
                    $chunk['message']['thinking'] = $chunk['message']['reasoning_content'];
                }
            }
            if (isset($chunk['choices'][0]['finish_reason'])) {
                $chunk['done'] = true;
                $chunk['done_reason'] = $chunk['choices'][0]['finish_reason'];
            }
        }

        //$this->oai->debug("Adding Chunk", $chunk);
        foreach ($chunk as $key => $value) {
            if ($key === 'message') {
                continue; // Skip message - handled separately for content accumulation
            }
            $this->$key = $value;
        }

        if (!isset($chunk['message']) || !is_array($chunk['message'])) {
            return '';
        }

        // Handle message content (regular content)
        if (!isset($chunk['message']['thinking'])) {
            $piece = isset($chunk['message']['content']) ? $chunk['message']['content'] : '';
            $this->content .= $piece;
            $this->is_thinking = false;
            return $piece;
        }

        $this->thinking .= $chunk['message']['thinking'];
        $this->is_thinking = true;
        return $chunk['message']['thinking'];
         
    }
    
    function reply($message, $options = array())
    {
        // Start with conversation history
        $messages = isset($this->call->messages) ? $this->call->messages : array();

        if(!empty($this->content)) {
            // add the last response from the model to the messages
            $messages[] = array('role' => 'assistant', 'content' => $this->content);
        }
        
        // Add assistant response from this object (if available)
        if (!empty($this->choices) && isset($this->choices[0]['message'])) {
            $messages[] = $this->choices[0]['message'];
        }
        
        // Add new user message
        $messages[] = array('role' => 'user', 'content' => $message);
        
        // Send new request with conversation history
        return $this->oai->chat(array(
            'model' => $this->model,
            'messages' => $messages,
            'options' => $options
        ));
    }
}

