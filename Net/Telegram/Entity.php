<?php

class Net_Telegram_Entity {
    
    var $_telegram;
    
    var $_types = array();
    function __construct($tg, $o=false)
    {
        $this->_telegram = $tg;
        if ($o === false) {
            return;
        }
        foreach((array)$o as $k=>$v) {
            if (substr($k,0,1) == '_') {
                continue;
            }
            if (isset($this->_types[$k.'[]'])) {
                // expecting an array of types..
                $vv = array();
                foreach($v as $vi) {
                    $vv[] = $tg->factory($this->_types[$k.'[]'], $vi);
                }
                $this->$k = $vv;
            }
            
            if (isset($this->_types[$k])) {
                $this->$k = $tg->factory($this->_types[$k], $v);
                continue;
            }
            
            $this->$k = $v;
        }
    }
    function token()
    {
        return $this->_telegram->token();
    }
    
    
    
}
