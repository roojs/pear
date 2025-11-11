<?php

class_exists('Net_Ollama_Call') || require_once 'Net/Ollama/Call.php';

class Net_Ollama_Call_Chat extends Net_Ollama_Call {
    var $_url = 'chat';
    /**
     * @var string Model name (required)
     */
    var $model;
    /**
     * @var array Chat history as an array of message objects (each with a role and content) (required)
     */
    var $messages = array();
    /**
     * @var array Optional list of function tools the model may call during the chat
     */
    var $tools = array();
    /**
     * @var bool Stream response (set to true automatically if callback is provided)
     */
    var $stream;
    /**
     * @var string|object Format to return a response in. Can be 'json' or a JSON schema
     */
    var $format;
    /**
     * @var object Runtime options that control text generation
     */
    var $options;
    /**
     * @var bool When true, returns separate thinking output in addition to content
     */
    var $think;
    /**
     * @var string|number Model keep-alive duration (for example '5m' or 0 to unload immediately)
     */
    var $keep_alive;
    
    function __construct($oai, $args = array())
    {
        // If args is a string, convert it to messages array
        if (is_string($args)) {
            $args = array('messages' => array(array('role' => 'user', 'content' => $args)));
        }
        
        parent::__construct($oai, $args);
        
        // If tools are configured at Ollama level, merge them into $this->tools
        if (!empty($this->oai->tools)) {
            foreach ($this->oai->tools as $tool) {
                $this->tools[] = $tool;
            }
        }
        
        // If callback is set on the Ollama instance, enable streaming
        if (!empty($this->oai->callback) && is_callable($this->oai->callback)) {
            $this->stream = true;
        }
    }
    
    function execute()
    {
        return $this->process($this->send());
    }
    
    function process($response)
    {
        if (!is_object($response)) {
            $response = $this->oai->response('Chat', json_decode($response, true));
        }
        $this->response = $response;
        $this->response->call = $this;
        return $response;
          
    }
}

