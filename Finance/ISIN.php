<?php
class Finance_ISIN 
{
    static $maps = array();

    var $map = array();

    function getMap($stockCode = false)
    {
        return $this->map;
    }

    function getISIN($stockCode) 
    {
        $ar = explode('.', $stockCode);
        // invalid stock code
        if(count($ar) != 2) {
            return false;
        }

        // load the map if it is not loaded
        if(!isset(self::$maps[$ar[1]])) {
            $file = dirname(__FILE__) . '/ISIN/' . $ar[1] . '.php';

            // invalid / not supported
            if(!file_exists(dirname(__FILE__) . '/ISIN/' . $ar[1] . '.php')) {
                return false;
            }
            
            include_once $file;

            $cls = 'Finance_ISIN_' . $ar[1];

            $isinCls = new $cls();
    
            self::$maps[$ar[1]] = $isinCls->getMap($stockCode);
        }

        return isset(self::$maps[$ar[1]][$stockCode]) ? self::$maps[$ar[1]][$stockCode] : false;
    }
}
