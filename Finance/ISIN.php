<?php
class Finance_ISIN 
{

    var $map = array();

    function getCountryISIN($stockCode)
    {

    }

    function getISIN($stockCode) 
    {
        $ar = explode('.', $stockCode);

        // invalid stock code
        if(count($ar) != 2) {
            return false;
        }

        $file = dirname(__FILE__) . '/ISIN/' . $ar[1] . '.php';

        require_once $file;
        
        $cls = 'Finance_ISIN_' . $ar[1];

        // invalid / not supported
        if(!class_exists($cls)) {
            return false;
        }

        // new cls
        // realGetISIN

        $cls = 'Finance_ISIN_' . $ar[1];
        $cls::updateMap($stockCode);
        self::$maps[$ar[1]] = $cls::$map;

        return isset(self::$maps[$ar[1]][$stockCode]) ? self::$maps[$ar[1]][$stockCode] : false;
    }
}
