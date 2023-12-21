<?php
class Finance_ISIN_SG extends Finance_ISIN
{
    static function updateMap($stockCode)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.boerse-stuttgart.de/api/bsg-feature-navigation/Search/PostSearchInput');   
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
            http_build_query(
                array(
                    'searchSubmit' => $stockCode,
                    'language' => 'en', 
                    'datasource' => '5849b3c3-7bd3-4570-9fed-df92b0788426'
                )
            )
        );     
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                // "Cookie: website#lang=en; COOKIE_CONSENT=ALLOW_REQUIRED%2CALLOW_FUNCTIONAL%2CALLOW_MARKETING%2CALLOW_YOUTUBE; _ga=GA1.1.1221475436.1702970441; _fbp=fb.1.1702970440692.739642396; _ga_FPYLH5F3W2=GS1.1.1703148889.5.1.1703148919.0.0.0",
                // "Accept-Language: en",
                // "Accept: application/json, text/plain, */*",
                "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36",
                "Content-Type: application/x-www-form-urlencoded"
            )
        );
        curl_setopt($ch, CURLOPT_COOKIE,  "website#lang=en;");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $str = curl_exec($ch);
        curl_close($ch);


        var_dump(curl_getinfo($ch));
        var_dump($str);
        die('test');

        if($isin) {
            self::$map[$stockCode] = $isin;
        }
    }
}