<?php

abstract class Net_Ollama_Call {
    protected $oai; // to prevent looping
    var $id;
    var $response;
    static $excluded = array('id', 'response');
    protected $exclude = array(); // eg.'id', 'response');
    protected $_url = '';
    protected $_method = 'POST';
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
        }
        
        // Use _url property for endpoint, _method for HTTP method
        $url = $this->oai->url . '/' . $this->_url;
        
        if ($this->_method === 'GET') {
            $url .= (!empty($params) ? '?' . http_build_query($params) : '');
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $headers = array();
        if ($this->_method === 'POST') {
            $headers[] = 'Content-Type: application/json';
        }
        if (!empty($this->oai->key)) {
            $headers[] = 'Authorization: Bearer ' . $this->oai->key;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($this->_method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
        
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}

