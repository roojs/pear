<?php
class Finance_ISIN 
{
    static $maps = array();

    static $map = array();

    static function updateMap($stockCode)
    {
    }

    static function getISIN($stockCode) 
    {
        $ar = explode('.', $stockCode);

        // invalid stock code
        if(count($ar) != 2) {
            return false;
        }

        $file = dirname(__FILE__) . '/ISIN/' . $ar[1] . '.php';

        // invalid / not supported
        if(!file_exists($file)) {
            return false;
        }

        include_once $file;

        $cls = 'Finance_ISIN_' . $ar[1];
        // update map if necessary
        $cls::updateMap($stockCode);

        // load the map if it is not loaded
        if(!isset(self::$maps[$ar[1]])) {
            
            include_once $file;

            $cls = 'Finance_ISIN_' . $ar[1];
            $cls::updateMap($stockCode);
            self::$maps[$ar[1]] = $cls::$map;
        }

        return isset(self::$maps[$ar[1]][$stockCode]) ? self::$maps[$ar[1]][$stockCode] : false;
    }
}
