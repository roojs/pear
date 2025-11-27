<?php

abstract class Net_Ollama_Call {
    protected $oai; // to prevent looping
    var $id;
    var $response;
    static $excluded = array('id', 'response');
    protected $exclude = array(); // eg.'id', 'response');
    protected $_url = '';
    protected $_method = 'POST';
    protected $_stream_buffer = '';
    protected $_chat_stream = false;
     
    function __construct($oai, $args = array())
    {
        $this->oai = $oai;
        $this->id = count($oai->calls);
        // Universal constructor - populate properties from args
        foreach ((array)$args as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }
    
    abstract function execute();
    abstract function process($response);
    
    function send()
    {
        // Build params from object properties
        $params = array();
        // exclude should look at values in this->exclude and static $exclude and also ignore '_' prefixed properties
        foreach ((array)$this as $k => $v) {
            if (in_array($k, self::$excluded) || in_array($k, $this->exclude) || strpos($k, '_') === 0) {
                continue;
            }
            if (isset($this->$k) && $this->$k !== false) {
                $params[$k] = $this->$k;
            }
            if($k == 'stream' && $this->$k == false) {
                $params[$k] = false;
            }
        }

        var_dump($this->stream);
        var_dump($params['stream']);
        
        // Use _url property for endpoint, _method for HTTP method
        $url = $this->oai->url . '/' . $this->_url;
        
        if ($this->_method === 'GET') {
            $url .= (!empty($params) ? '?' . http_build_query($params) : '');
        }
        
        $this->oai->debug("Sending Request", array(
            'method' => $this->_method,
            'url' => $url,
            'params' => $params
        ));
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Build headers first (needed for curl command output)
        $headers = array();
        if ($this->_method === 'POST') {
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($this->oai->key)) {
            $headers[] = 'Authorization: Bearer ' . $this->oai->key;
        }
        
        // Check if streaming is enabled (API always streams when stream=true)
        if (!empty($params['stream'])) {
            // Streaming requires a callback - throw exception if not provided
            if (empty($this->oai->callback) || !is_callable($this->oai->callback)) {
                throw new Exception('Streaming requires a callback function. Set callback property on Net_Ollama instance before enabling stream.');
            }
            
            $this->oai->debug("Initializing Streaming", array(
                'callback_set' => !empty($this->oai->callback),
                'callback_callable' => is_callable($this->oai->callback),
                'stream_flag' => $params['stream']
            ));
            
            // Output curl command for streaming requests
            if ($this->oai->debug) {
                $curl_cmd = $this->_build_curl_command($url, $params, $headers, true);
                echo "[DEBUG] Curl Command (Streaming):\n";
                echo $curl_cmd . "\n\n";
                flush();
            }
            
            // For streaming, we need to read the response line by line
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, array($this, '_stream_write_callback'));
            $this->_stream_buffer = '';
            // Determine response type from _url (chat -> Chat, generate -> Generate)
            $response_type = ucfirst($this->_url);
            $this->_chat_stream = $this->oai->response($response_type, array());
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($this->_method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
        
        if (!empty($params['stream'])) {
            curl_exec($ch);
            curl_close($ch);
              
            // Call callback once at the end with any remaining new text and final response
            // Note: We can't access the static variable here, so we'll pass empty string
            // and let the callback use the full content from the stream object
            $final_new_text = '';
            // Get content - Chat uses 'content', Generate uses 'response'
            $content = isset($this->_chat_stream->content) ? $this->_chat_stream->content : 
                       (isset($this->_chat_stream->response) ? $this->_chat_stream->response : '');
            if (strlen($content) > 0) {
                // For final callback, pass the full content as new text since we can't track
                // the last length from the static variable in this scope
                $final_new_text = $content;
            }
              
            call_user_func($this->oai->callback, $final_new_text, $this->_chat_stream);
             
            $this->oai->debug("Received Stream Response", (array)$this->_chat_stream);
            
            // Convert Response object to array for JSON encoding
            return  $this->_chat_stream;
        } 
        $result = curl_exec($ch);
        curl_close($ch);
        
        $this->oai->debug("Received Response", json_decode($result, true));
        
        return $result;
        
    }
    
    /**
     * Build curl command-line equivalent for debugging
     */
    function _build_curl_command($url, $params, $headers, $streaming = false)
    {
        $cmd = "curl -X " . $this->_method;
        
        // Add --no-buffer flag for streaming
        if ($streaming) {
            $cmd .= " --no-buffer";
        }
        
        // Add headers
        foreach ($headers as $header) {
            $cmd .= " \\\n  -H " . escapeshellarg($header);
        }
        
        // Add POST data
        if ($this->_method === 'POST' && !empty($params)) {
            $json_data = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $cmd .= " \\\n  -d " . escapeshellarg($json_data);
        }
        
        // Add URL
        $cmd .= " \\\n  " . escapeshellarg($url);
        
        return $cmd;
    }
    
    /**
     * CURL write callback for handling streaming responses
     */
    function _stream_write_callback($ch, $data)
    {
        static $last_content_length = 0;
        /*
        $this->oai->debug("Stream Callback Invoked", array(
            'data_length' => strlen($data),
            'buffer_length_before' => strlen($this->_stream_buffer),
            'data_preview' => substr($data, 0, 100)
        ));
        */
        // Append data to buffer
        $this->_stream_buffer .= $data;
        $new_text = '';
        // Process complete lines (JSON objects separated by newlines)
        while (($pos = strpos($this->_stream_buffer, "\n")) !== false) {
            $line = substr($this->_stream_buffer, 0, $pos);
            $this->_stream_buffer = substr($this->_stream_buffer, $pos + 1);
            /*
            $this->oai->debug("Processing Stream Line", array(
                'line_length' => strlen($line),
                'line_preview' => substr($line, 0, 200),
                'buffer_remaining' => strlen($this->_stream_buffer)
            ));
            */
            // Skip empty lines
            if (trim($line) === '') {
                $this->oai->debug("Skipping Empty Line", array());
                continue;
            }
            
            // Parse JSON chunk
            $chunk = json_decode($line, true);
            if ($chunk === null) {
                $this->oai->debug("Invalid JSON Chunk", array(
                    'line' => $line,
                    'json_error' => json_last_error_msg()
                ));
                continue; // Skip invalid JSON
            }
            /*
            $this->oai->debug("Valid JSON Chunk Parsed", array(
                'chunk_keys' => array_keys($chunk),
                'has_message' => isset($chunk['message']),
                'has_content' => isset($chunk['message']['content']),
                'content_length' => isset($chunk['message']['content']) ? strlen($chunk['message']['content']) : 0
            ));
            */
            // Add chunk to stream response
            $new_text .= $this->_chat_stream->addChunk($chunk);
            
            if (strlen($new_text) > 0) {
               
                
                // Call callback with new text as first arg, full stream as second
                call_user_func($this->oai->callback, $new_text, $this->_chat_stream);
                
                
            }
        }
        
        /*
        
        $this->oai->debug("Stream Callback Complete", array(
            'bytes_processed' => strlen($data),
            'buffer_length_after' => strlen($this->_stream_buffer)
        ));
        */
        return strlen($data); // Return number of bytes processed
    }
}
 