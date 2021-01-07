<?php


class Net_Telegram {
    
    
    
    function __construct($tok)
    {
        $this->_token = $tok;
        
    }
    
    function factory($e, $o=false) {
        require_once 'Net/Telegram/'.ucfirst($e).'.php';
        $cls = 'Net_Telegram_'. $e;
        return new $cls($o);
    }
    
}
