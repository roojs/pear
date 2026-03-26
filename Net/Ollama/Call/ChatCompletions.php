<?php

class_exists('Net_Ollama_Call_Chat') || require_once 'Net/Ollama/Call/Chat.php';

class Net_Ollama_Call_ChatCompletions extends Net_Ollama_Call_Chat {
    var $_url = 'v1/chat/completions';
    /** Ollama-native /chat fields not sent on /v1/chat/completions (see Net_Ollama_Call::send). */
    var $_exclude = array('format', 'think', 'options', 'keep_alive');

    /**
     * @var string|object OpenAI response_format (converted from format)
     */
    var $response_format;
    /**
     * DeepSeek Chat Completions request field "thinking" with "type" enabled|disabled.
     * Not OpenAI Responses API "reasoning.effort"; see constructor comments for OpenAI vs DeepSeek.
     * @link https://api-docs.deepseek.com/api/create-chat-completion
     */
    var $thinking;
    
    function __construct($oai, $args = array())
    {
        $args = (array)$args;

        // Ollama `format` is not sent; map JSON mode to Chat Completions `response_format` only.
        if (isset($args['format']) && $args['format'] == 'json') {
            $args['response_format'] = array('type' => 'json_object');
        }

        // `thinking` is a DeepSeek Chat Completions extension only (URL contains "deepseek").
        // @link https://api-docs.deepseek.com/api/create-chat-completion
        if (stripos($oai->url, 'deepseek') !== false && isset($args['think']) && !isset($args['thinking'])) {
            $args['thinking'] = array(
                'type' => $args['think'] ? 'enabled' : 'disabled'
            );
        }

        var_dump($args);
        die('test');

        parent::__construct($oai, $args);
    }
    
    /**
     * Override response type determination for parent's send() and process() methods
     */
    function getResponseType()
    {
        return 'Chat';
    }
    
    
    
    /**
     * cURL write callback: OpenAI-style SSE (lines like "data: {...}").
     */
    function streamWriteCallback($ch, $data)
    {
        // Append data to buffer
        $this->_stream_buffer .= $data;
        $new_text = '';
        
        // Process complete lines (SSE format: "data: {...}\n")
        while (($pos = strpos($this->_stream_buffer, "\n")) !== false) {
            $line = substr($this->_stream_buffer, 0, $pos);
            $this->_stream_buffer = substr($this->_stream_buffer, $pos + 1);
            
            // Skip empty lines
            if (trim($line) === '') {
                continue;
            }
            
            // Check for [DONE] marker
            if (trim($line) === 'data: [DONE]') {
                continue;
            }
            
            // Parse SSE format: "data: {...}"
            if (strpos($line, 'data: ') === 0) {
                $jsonStr = substr($line, 6); // Remove "data: " prefix
                $chunk = json_decode($jsonStr, true);
                
                if ($chunk === null) {
                    $this->oai->debug("Invalid JSON Chunk in SSE", array(
                        'line' => $line,
                        'json_error' => json_last_error_msg()
                    ));
                    continue; // Skip invalid JSON
                }
                
                // Add chunk to stream response (Response class handles OpenAI format conversion)
                $chunkText = $this->_chat_stream->addChunk($chunk);
                if ($chunkText) {
                    $new_text .= $chunkText;
                }
            }
        }
        
        if (strlen($new_text) > 0) {
            // Call callback with new text as first arg, full stream as second
            call_user_func($this->oai->callback, $new_text, $this->_chat_stream);
        }
        
        return strlen($data); // Return number of bytes processed
    }
    
}
