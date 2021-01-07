<?php

require_once 'Call.php';
class Net_Telegram_SetMyCommands extends Net_Telegram_Call {
    
    var $commands; // array of commands
    
    var $_types = array(
        'commands[]' => 'BotCommand'
    );
    
    function send()
    {
          $res = parent::send();
       // print_R($res);
        return $this->_telegram->factory('Message',$res);
    }
    
    
}

    