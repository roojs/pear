<?php

class Net_Ollama {
    var $key = ''; // ollama key
    var $url = 'http://localhost:11434/api'; // ollama url
    var $tools = array();
    var $calls = array();
    var $callback = null; // Callback function for streaming: function($partial_response, $full_response)
    var $debug = false; // Debug mode - when true, prints all send/receive data
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
         
        // Only append /api if URL doesn't already end with /api
        if (!preg_match('#/api$#', rtrim($this->url, '/'))) {
            $this->url = rtrim($this->url, '/') . '/api';
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
    
    function chat($params)
    {
        return $this->call('Chat', $params);
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
       
        // var_dump("DATA");
        // var_dump($data);
        // var_dump("JSON ENCODE DATA");
        // var_dump(json_encode($data));
        // var_dump("JSON DECODE DATA");
        // var_dump(json_decode(json_encode($data)));
        print_r(json_decode(json_encode($data)));
        echo "\n";
       
        flush();
    }
}

