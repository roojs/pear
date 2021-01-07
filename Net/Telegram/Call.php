<?php

require_once 'Entity.php';
class Net_Telegram_Call extends Net_Telegram_Entity {
   
    
    function send($tok)
    {
        $params = array();
        foreach((array) $this as $k=>$v) {
            if (substr($k,0,1) =='_') {
                continue;
            }
            $params[$k] = $v;
        }
        
        $cls = explode('_',get_class($this));
        $method = lcfirst(array_pop($cls));
        $ch = curl_init("https://api.telegram.org/bot{$tok}/".lcfirst($method));
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }
    
    
}

    