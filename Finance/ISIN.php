<?php
class Finance_ISIN 
{
    var $map = array();

    function getISIN($stockCode) 
    {
        $ar = explode('.', $stockCode);
        // invalid stock code
        if(count($ar) != 2) {
            return false;
        }

        $file = dirname(__FILE__) . '/ISIN/' . $ar[1] . '.php';

        // invalid / not supported
        if(!file_exists(dirname(__FILE__) . '/ISIN/' . $ar[1] . '.php')) {
            return false;
        }
        
        include $file;

        return isset($this->map[$stockCode]) ? $this->map[$stockCode] : false;
    }
}
