<?php
class Finance_ISIN 
{
    static $maps = array();

    var $map = array();

    function getISIN($stockCode) 
    {
        $ar = explode('.', $stockCode);
        // invalid stock code
        if(count($ar) != 2) {
            return false;
        }

        // load the map if it is not loaded
        if(!isset(self::$maps[$ar[1]])) {
            $file = dirname(__FILE__) . '/ISIN/' . $ar[1] . '.php';

            // invalid / not supported
            if(!file_exists(dirname(__FILE__) . '/ISIN/' . $ar[1] . '.php')) {
                return false;
            }
            
            include_once $file;

            $cls = 'Finance_ISIN_' . $ar[1];

            $isinCls = new $cls();
    
            self::$maps[$ar[1]] = $isinCls->getMap();
        }

        return isset(self::$maps[$ar[1]][$stockCode]) ? self::$maps[$ar[1]][$stockCode] : false;
    }

    function getSGISIN() 
    {
        return false;

        $ch = curl_init();
        // $f = tmpfile();
        curl_setopt($ch, CURLOPT_URL, 'https://www.boerse-stuttgart.de/api/bsg-feature-navigation/Search/PostSearchInput');   
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
            http_build_query(
                array(
                    'searchSubmit' => 'sow',
                    'language' => 'en', 
                    'datasource' => '5849b3c3-7bd3-4570-9fed-df92b0788426'
                )
            )
        );     
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                "Accept-Language: en",
                "Accept: application/json, text/plain, */*",
                "Content-Type: application/x-www-form-urlencoded"
            )
        );
        curl_setopt($ch, CURLOPT_COOKIE,  "website#lang=en;");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_VERBOSE, true);
        // curl_setopt($ch, CURLOPT_STDERR, $f);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $str = curl_exec($ch);
        // fseek($f,0);
        // var_dump( fread($f, 32*1024) );
        // fclose($f);
        curl_close($ch);


        var_dump(curl_getinfo($ch));
        var_dump("GET SG ISIN\n");
        var_dump($str);
        die('test');
    }
}
