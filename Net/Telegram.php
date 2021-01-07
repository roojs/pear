<?php


class Net_Telegram {
    
    
    static $tokens = array();
    
    var $id;
    
    function __construct($tok)
    {
        static $id=0;
        $this->id = $id;
        self::$tokens[$id++]  = $tok;
    }
    
    function factory($e, $o=false) {
        require_once 'Net/Telegram/'.ucfirst($e).'.php';
        $cls = 'Net_Telegram_'. $e;
        $ret = new $cls($o);
        $ret->_telegram = $this;
    }
    
}
