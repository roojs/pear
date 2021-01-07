<?php

require_once 'Entity.php';
class Net_Telegram_SendMessage extends Net_Telegram_Entity {
    
    var $chat_id; // the user id... or channel name ?
    var $text;
    var $parse_mode; // HTML|Markdown|MarkdownV2
    
    var $_types = array(
        
    );
    
    function send($tok)
    {
        '{"chat_id": "210830759", "text": "This is a test from curl", "disable_notification": true}' \ 
    }
    
    
}

    