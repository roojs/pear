<?php

require_once 'Entity.php';
class Net_Telegram_Update extends Net_Telegram_Entity {
    
    var $update_id;
    
    var $_types = array(
        'message' => 'Message',
    );
    
    
    
}
