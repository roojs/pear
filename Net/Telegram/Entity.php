<?php

class Net_Telegram_Entity {
    
    
    var $_types = array();
    function __construct($o=false)
    {
        if ($o === false) {
            return;
        }
        foreach((array)$o as $k=>$v) {
            if (substr($k,0,1) == '_') {
                continue;
            }
            if (isset($this->_types[$k])) {
                require_once 'Net/Telegram/'. $this->_types[$k] .'.php';
                $cls = 'Net_Telegram_'. $this->_types[$k];
                $this->$k = new $cls($v);
                continue;
            }
            
            $this->$k = $v;
        }
    }
    
    
}
