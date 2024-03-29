<?php
class Finance_ISIN_SG extends Finance_ISIN
{
    function getLocationISIN($stockcode)
    {
        $ar = explode('.', $stockcode);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.boerse-stuttgart.de/api/bsg-feature-navigation/Search/PostSearchInput');   
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
            http_build_query(
                array(
                    'searchSubmit' => $ar[0],
                    'language' => 'en', 
                    'datasource' => '5849b3c3-7bd3-4570-9fed-df92b0788426'
                )
            )
        );     
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