<?php

class_exists('Net_Ollama_Call') || require_once 'Net/Ollama/Call.php';

class Net_Ollama_Call_Generate extends Net_Ollama_Call {
    var $_url = 'generate';
    /**
     * @var string Model name (required)
     */
    var $model;
    /**
     * @var string Text for the model to generate a response from (required)
     */
    var $prompt;
    /**
     * @var string Used for fill-in-the-middle models, text that appears after the user prompt and before the model response
     */
    var $suffix;
    /**
     * @var array Base64-encoded images for models that support image input
     */
    var $images = array();
    /**
     * @var string|object Format to return a response in. Can be 'json' or a JSON schema
     */
    var $format;
    /**
     * @var string System prompt for the model to generate a response from
     */
    var $system;
    /**
     * @var bool Stream response (set to true automatically if callback is provided)
     */
    var $stream;
    /**
     * @var bool When true, returns the raw response from the model without any prompt templating
     */
    var $raw;
    /**
     * @var object Runtime options that control text generation
     */
    var $options;
    /**
     * @var bool|string When true, returns separate thinking output in addition to content. Can be a boolean (true/false) or a string ("high", "medium", "low") for supported models.
     */
    var $think;
    /**
     * @var string|number Model keep-alive duration (for example '5m' or 0 to unload immediately)
     */
    var $keep_alive;

    var $context;
    
    function __construct($oai, $args = array())
    {
        // If args is a string, convert it to prompt
        if (is_string($args)) {
            $args = array('prompt' => $args);
        }
        
        parent::__construct($oai, $args);
        
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
            $response = $this->oai->response('Generate', json_decode($response, true));
        }
        $this->response = $response;
        $this->response->call = $this;
        return $response;
          
    }
}

