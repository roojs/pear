<?php
class Finance_ISIN_L extends Finance_ISIN
{
    function getLocationISIN($stockcode)
    {
        $ar = explode('.', $stockcode);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.londonstockexchange.com/api/gw/lse/instruments/alldata/' . $ar[0]);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36",
                "Content-Type: application/x-www-form-urlencoded"
            )
        );
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $str = curl_exec($ch);
        curl_close($ch);

        $isin = false;

        var_dump($str);
        die('test');

        return $isin;
    }
}