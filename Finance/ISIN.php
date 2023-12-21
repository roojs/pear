<?php
class Finance_ISIN 
{
    static $codes;

    function updateMap($stockCode)
    {
        var_dump(get_class());
        die('test');
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
            $isinCls->updateMap($stockCode);
    
            self::$maps[$ar[1]] = $isinCls->map;
        }

        return isset(self::$maps[$ar[1]][$stockCode]) ? self::$maps[$ar[1]][$stockCode] : false;
    }
}
