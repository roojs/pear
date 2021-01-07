<?php

require_once 'Entity.php';
class Net_Telegram_Call extends Net_Telegram_Entity {
    
    var $chat_id; // the user id... or channel name ?
    var $text;
    var $parse_mode; // HTML|Markdown|MarkdownV2
    var $entities; // array of entitiels
    var $disable_web_page_preview; ///??
    var $reply_to_message_id;
    var $allow_sending_without_reply;  // if you are using reply_to and do not want to validate it...
    var $reply_markup; // markup keyboards?!?!
    
    var $_types = array(
        
    );
    
    function send($tok)
    {
        $ch = curl_init($website . '/sendMessage');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
    }
    
    
}

    