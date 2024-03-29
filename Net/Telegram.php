<?php


class Net_Telegram {
    
    
    static $tokens = array();
    
    var $id;
    
    function __construct($tok)
    {
        static $id=0;
        $this->id = $id;
        self::$tokens[$id++]  = $tok; // hiddne from print_R
    }
    
    function factory($e, $o=false)
    {
        require_once 'Net/Telegram/'.ucfirst($e).'.php';
        $cls = 'Net_Telegram_'. $e;
        return new $cls($this, $o);
        
    }
    function token()
    {
        return self::$tokens[$this->id];
    }
    
    function escape($text)
    {
        return str_replace(array(
                 '\\', '_', '*', '[', ']', '(', ')', '~', '`',  '>',
                '<', '&', '#', '+', '-', '=', '|', '{', '}', '.', '!'
                ), array(
                    '\\\\', '\\_', '\\*', '\\[', '\\]', '\\(', '\\)', '\\~', '\\`',  '\\>',
                    '\\<', '\\&', '\\#', '\\+', '\\-', '\\=', '\\|', '\\{', '\\}', '\\.', '\\!'
                ),
                $text);
    }
            
}
