<?php
class Finance_ISIN 
{

    var $map = array();

    function getISIN($stockcode, $exchange) 
    {
        $ar = explode('.', $stockcode);

        if(count($ar) != 2) {
            // get isin by exchange
            // support NYSE
            switch($exchange) {
                case 'NYSE':
                case 'NASDAQ':
                    return $this->getExchangeISIN($stockcode, $exchange);
                default:
                    return false;
                
            }
            
        }
        if ($ar[1] == 'PA') {
            return $this->getExchangeISIN($ar[0], 'EURONEXT');
        }
        if ($ar[1] == 'SZ') {
            return $this->getExchangeISIN($ar[0], 'SZSE');
        }

        // get isin by location

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

    // data from Trading View (A more reliable source should be used if found)

    function getExchangeISIN($stockcode, $exchange)
    {
        $ar = explode('.', $stockcode);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.tradingview.com/symbols/' . $exchange . '-' . $stockcode .'/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $str = curl_exec($ch);
        curl_close($ch);

        $matches = array();
        preg_match('/window.initData.symbolInfo = ({.*});/', $str, $matches);

        if(empty($matches)) {
            return false;
        }

        $ret = json_decode($matches[1]);

        if(!empty($ret->isin)) {
            return $ret->isin;
        }
        if(!empty($ret->isin_displayed)) {
            return $ret->isin_displayed;
        }
        return false;
    }

    function getLocationISIN($stockcode)
    {
        return isset($this->map[$stockcode]) ? $this->map[$stockcode] : false;
    }
}
