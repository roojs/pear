<?php

class_exists('Net_Ollama_Call') || require_once 'Net/Ollama/Call.php';

class Net_Ollama_Call_Ps extends Net_Ollama_Call {
    var $_url = 'ps';
    var $_method = 'GET';
    var $response = array();
    
    function execute()
    {
        return $this->process($this->send());
    }
    
    function process($response)
    {
        $data = json_decode($response, true);
          
        foreach ($data['models'] as $model) {
             
            $model['name'] = $model['model'];
             
            $this->response[] = $this->oai->response('Model', $model);
        }
         
        return $this->response;
    }
}

