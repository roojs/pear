<?php
class Finance_ISIN_L extends Finance_ISIN
{
    function getLocationISIN($stockcode)
    {
        $ar = explode('.', $stockcode);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.londonstockexchange.com/stock/' . $ar[0] . '/search');
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

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($str);
        var_dump($str);
        $xpath = new DomXPath($dom);
        $items = $xpath->query("//app-index-item[contains(@class,'index-item')]");
        foreach($items as $item) {
            var_dump($item);
            /*
            if(substr($item->nodeValue, 0, 5) != 'ISIN ') {
                continue;
            }
            $isin = substr($item->nodeValue, 5);
            */


        }
        die('test');

        return $isin;
    }
}