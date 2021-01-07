<?php

require_once 'Call.php';
class Net_Telegram_SendMessage extends Net_Telegram_Call {
    
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
    
    function send()
    {
          $res = parent::send();
        print_R($res);
        return $this->_telegram->factory('Message',$res);
    }
    
    
}

    