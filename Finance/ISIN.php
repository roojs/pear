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

    function getSGISIN($stockCode) 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, 'https://www.boerse-stuttgart.de/api/bsg-feature-navigation/Search/PostSearchInput');        
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/x-www-form-urlencoded"
            )
        )
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $str = curl_exec($ch);
        curl_close($ch);
    }
}
