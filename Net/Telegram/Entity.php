<?php

class Net_Telegram_Entity {
    
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
