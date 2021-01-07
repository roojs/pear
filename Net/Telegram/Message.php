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
    
    // quick reply..
    function reply($str)
    {
        
        $mg = $this->_telegram->factory('SendMessage', array_merge(
            is_array($str)  ? $str : array('text' => $str),
            array(
                'chat_id' => $this->from->id,
                'reply_to_message_id' => $this->message_id,
                'allow_sending_without_reply' => true
            )
        ));
        $mg->send();
        
    }
    
}
