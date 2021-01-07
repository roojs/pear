<?php

require_once 'Entity.php';
class Net_Telegram_Update extends Net_Telegram_Entity {
    
    
    function __construct($o)
    {
        parent::__construct($o);
        if (isset($this->message)) {
            $this->message = new Net_Telegram_Update($this->message);
        }
    }
    
}
