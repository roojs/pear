<?php

require_once 'Entity.php';
class Net_Telegram_Message extends Net_Telegram_Entity {
    
    var $message_id;
    var $from;
    var $chat;
    var $date;
    var $text;
    
    var $_types = array(
        'from' => 'User',
        'chat' => 'Chat',
        'contact' => 'Contact'
    );
    
    
    function send ($tok)
    {
        
    }
    
    
}
