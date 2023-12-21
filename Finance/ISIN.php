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

        $c = new cls();
        return $c->getCountryISIN($stockCode);
    }
}
