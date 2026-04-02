<?php

/**
 * HTTP client for Ollama and OpenAI-compatible chat APIs.
 *
 * {@see $url}: For native Ollama, use a base ending in `/api` (see default). For
 * OpenAI-compatible servers, use the API origin without `/api` (e.g. `https://api.deepseek.com`)
 * and call {@see chatCompletions()}.
 */
class Net_Ollama {
    var $key = ''; // ollama key
    /** @var string Base URL: native Ollama ends with `/api`; OpenAI-compatible origin has no `/api`. */
    var $url = 'http://localhost:11434/api';
    var $tools = array();
    var $calls = array();
    var $callback = null; // Callback function for streaming: function($partial_response, $full_response)
    var $debug = false; // Debug mode - when true, prints all send/receive data
    var $timeout = 300; // Request timeout in seconds (default: 5 minutes)
    static $id = 0;
    
    function __construct($options = array())
    {
        // Universal constructor - accepts array or object
        $options = (array)$options;
        
        // Populate properties from options
        foreach ($options as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }

        // Process tools if provided
        if (isset($options['tools'])) {
            class_exists('Net_Ollama_Tool') || require_once 'Net/Ollama/Tool.php';
            foreach ($options['tools'] as $tool) {
                $this->tools[] = new Net_Ollama_Tool($tool);
            }
        }
        // Restore calls if provided
        if (isset($options['calls'])) {
            foreach ($options['calls'] as $call_data) {
                if (isset($call_data['_url'])) {
                    $method = ucfirst($call_data['_url']);
                    $cls = 'Net_Ollama_Call_' . $method;
                    class_exists($cls) || require_once 'Net/Ollama/Call/' . $method . '.php';
                    $call = new $cls($this, $call_data);
                    $this->calls[] = $call;
                }
            }
        }
    }
    
    function response($type, $data)
    {
        // Always set id using static counter
         
        $cls = 'Net_Ollama_Response_' . $type;
        class_exists($cls) || require_once 'Net/Ollama/Response/' . $type . '.php';
        return new $cls($this, $data);
    }
    
    private function call($method, $args = array())
    {
        $cls = 'Net_Ollama_Call_' . $method;
        class_exists($cls) || require_once 'Net/Ollama/Call/' . $method . '.php';
        $call = new $cls($this, $args);
        $id = count($this->calls);
        $this->calls[$id] = $call;
        return $call->execute();
    }
    
    /**
     * Native Ollama chat: `POST {url}/chat` (url should end with `/api`).
     * For OpenAI-compatible `…/v1/chat/completions`, use {@see chatCompletions()} instead.
     *
     * @param array|string $params See Net_Ollama_Call_Chat
     */
    function chat($params)
    {
        return $this->call('Chat', $params);
    }

    /**
     * OpenAI-compatible chat: `POST {url}/v1/chat/completions`.
     * Set {@see $url} to the service root without a trailing `/api` (e.g. `https://api.deepseek.com`).
     *
     * @param array|string $params See Net_Ollama_Call_ChatCompletions
     * @return Net_Ollama_Response_Chat Normalized like native chat (OpenAI payloads adapted in that class).
     */
    function chatCompletions($params)
    {
        return $this->call('ChatCompletions', $params);
    }

    function models($params = array())
    {
        return $this->call('Models', array($params));
    }
    
    function ps($params = array())
    {
        return $this->call('Ps', array($params));
    }
    
    function to_string()
    {
        return json_encode((array)$this);
    }
    
    /**
     * Debug output method - only outputs if $this->debug is true
     * Can be called without checking - it checks internally
     */
    function debug($message, $data)
    {
        if (!$this->debug) {
            return;
        }
        echo "[DEBUG] " . $message . "\n";

        print_r(json_decode(json_encode($data)));
        echo "\n";
       
        flush();
    }

    /**
     * wrapper around throw exception.
     *
     * @param  string $message The Exception message to throw
     * @param  string $type The Exception code
     * @param  Throwable (optional)The previous exception used for the exception chaining
     * @throws Net_Ollama_Exception
     */
    function raise($message, $type, $previous = null)
    {
        require_once 'Net/Ollama/Exception.php';
        throw  Net_Ollama_Exception::factory($message, $type, $previous_exception);
    }
}

