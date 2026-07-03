<?php

class_exists('Net_Ollama_Call') || require_once 'Net/Ollama/Call.php';

class Net_Ollama_Call_Embed extends Net_Ollama_Call
{
    var $_url = 'embed';
    var $_method = 'POST';
    var $model;
    var $input;

    function execute()
    {
        return $this->process($this->send());
    }

    function process($response)
    {
        $response = $this->oai->response($this->getResponseType(), json_decode($response, true));
        $this->response = $response;
        return $response;
    }
}
