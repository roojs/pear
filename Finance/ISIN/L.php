<?php
class Finance_ISIN_L extends Finance_ISIN
{
    function getLocationISIN($stockcode)
    {
        $ar = explode('.', $stockcode);
        $isin = false;

        $jsonString = file_get_contents('https://api.londonstockexchange.com/api/gw/lse/instruments/alldata/' . $ar[0]);
        $jsonString = iconv("UTF-8", "ISO-8859-1//IGNORE", $jsonString);
        $json = json_decode($jsonString);
        $jsonError = json_last_error();

        if($jsonError != JSON_ERROR_NONE) {
            return $isin;
        }

        var_dump($json);
        die('test');

        return $isin;
    }
}