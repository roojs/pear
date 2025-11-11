<?php

class Net_Ollama_Response {
    var $id;
    protected $oai; // Reference to Net_Ollama instance
    
    function __construct($oai, $data)
    {
        $this->oai = $oai;
        $this->id = isset($data['id']) ? $data['id'] : Net_Ollama::$id++;
        $this->oai->debug("response ctor", $data);
        foreach ((array)$data as $k => $v) {
            if ($k != 'id') { // id already set
                $this->$k = $v;
            }
        }
    }
}

