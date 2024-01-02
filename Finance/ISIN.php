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

        // invalid / not supported
        if(!file_exists($file)) {
            return false;
        }

        require_once $file;

        $cls = 'Finance_ISIN_' . $ar[1];

        // invalid / not supported
        if(!class_exists($cls)) {
            return false;
        }

        $c = new $cls();
        return $c->getLocationISIN($stockcode);
    }

    function getISINFromExchange($stockcode, $exchange)
    {
        $ar = explode('.', $stockcode);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.tradingview.com/symbol/' . $exchange . '-' . $stockcode .'/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $str = curl_exec($ch);
        curl_close($ch);

        $matches = array();
        preg_match('/window.initData.symbolInfo = ({.*});/', $str, $matches);

        if(empty($matches)) {
            return false;
        }

        $ret = json_decode($matches[1]);
        var_dump($ret);
        var_dump($ret->isin);
        die('test');

        return $isin;
    }
}
