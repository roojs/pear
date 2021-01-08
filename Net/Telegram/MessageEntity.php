<?php

require_once 'Entity.php';
class Net_Telegram_MessageEnity extends Net_Telegram_Entity {
    
    var $type; // mention|hashtag|cashtag|bot_command|url|email|phone_number|bold|italic|underline|strikethrough|....
    var $offset;
    var $length;
    var $url;
    var $user;
    
    var $_types = array(
        'user' => 'User'
        
    );
    
    
}
