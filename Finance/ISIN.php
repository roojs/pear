<?php
class Finance_ISIN 
{

    var $map = array();

    function getLocationISIN($stockcode)
    {
        return isset($this->map[$stockcode]) ? $this->map[$stockcode] : false;
    }

    function getISIN($stockcode) 
    {
        $ar = explode('.', $stockcode);

        // invalid stock code
        if(count($ar) != 2) {
            return false;
        }

        $file = dirname(__FILE__) . '/ISIN/' . $ar[1] . '.php';

        include_once $file;

        $cls = 'Finance_ISIN_' . $ar[1];

        // invalid / not supported
        if(!class_exists($cls)) {
            return false;
        }

        $c = new $cls();
        return $c->getLocationISIN($stockcode);
    }
}
