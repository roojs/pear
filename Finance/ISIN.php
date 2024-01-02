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
        curl_setopt($ch, CURLOPT_URL, 'https://www.tradingview.com/symbols/' . $exchange . '-' . $stockcode .'/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $str = curl_exec($ch);
        curl_close($ch);

        // var_dump($str);

        $matches = array();
        preg_match('window.initData.symbolInfo = ({.*});', $str, $matches);
        var_dump($matches);
        die('test');

        $isin = false;

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($str);
        $xpath = new DomXPath($dom);
        $items = $xpath->query("//strong[@class='bsg-fs-header__subitem']");
        foreach($items as $item) {
            if(substr($item->nodeValue, 0, 5) != 'ISIN ') {
                continue;
            }
            $isin = substr($item->nodeValue, 5);


        }

        return $isin;
    }
}
