<?php

class_exists('Net_Ollama_Response_Chat') || require_once 'Net/Ollama/Response/Chat.php';

class Net_Ollama_Response_ChatCompletions extends Net_Ollama_Response_Chat {
    
    /**
     * @var string OpenAI response ID
     */
    var $id;
    /**
     * @var string OpenAI object type (e.g., "chat.completion")
     */
    var $object;
    /**
     * @var int Unix timestamp of response creation (OpenAI format)
     */
    var $created;
    /**
     * @var string System fingerprint (OpenAI)
     */
    var $system_fingerprint;
    
    /**
     * Convert OpenAI-compatible response format to Ollama native format
     * Modifies the data array in place to make it compatible with Ollama format
     * @param array $data Data array to convert (modified in place)
     * @param string $sourceField Field to use from choices[0]: 'message' for non-streaming, 'delta' for streaming
     */
    function _convertOpenAIToOllama(&$data, $sourceField = 'message')
    {
        // Convert 'created' to 'created_at' (keep created as-is, also add created_at for Ollama compatibility)
        if (isset($data['created'])) {
            $data['created_at'] = gmdate('Y-m-d\TH:i:s\Z', $data['created']);
        }
        
        // Convert choices[0].message or choices[0].delta to message
        if (isset($data['choices'][0][$sourceField])) {
            $data['message'] = $data['choices'][0][$sourceField];
            
            // Convert reasoning_content to thinking (DeepSeek thinking mode)
            // Keep reasoning_content as-is, also add thinking for Ollama compatibility
            if (isset($data['message']['reasoning_content'])) {
                $data['message']['thinking'] = $data['message']['reasoning_content'];
            }
        }
        
        // Convert finish_reason to done/done_reason
        if (isset($data['choices'][0]['finish_reason'])) {
            $data['done'] = true;
            $data['done_reason'] = $data['choices'][0]['finish_reason'];
        }
    }
    
    function __construct($oai, $data)
    {
        // Convert OpenAI format to Ollama format
        $this->_convertOpenAIToOllama($data, 'message');
        
        // Call parent constructor (handles message extraction)
        parent::__construct($oai, $data);
    }
    
    /**
     * Add a chunk to the stream response
     * Receives OpenAI-formatted chunk, converts it to Ollama format, then calls parent addChunk
     * Returns new text content
     */
    function addChunk($openaiChunk)
    {
        // Convert OpenAI format to Ollama format
        $this->_convertOpenAIToOllama($openaiChunk, 'delta');
        
        // Call parent addChunk with converted Ollama-formatted chunk
        return parent::addChunk($openaiChunk);
    }
}
