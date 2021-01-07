<?php

require_once 'Entity.php';
class Net_Telegram_Message {
    
    
    function __construct($o=false)
    {
        if ($o === false) {
            return;
        }
        foreach((array)$o as $k=>$v) {
            $this->$k = $v;
        }
    }
    
    
}
