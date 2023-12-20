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

        if($ar[1] == 'SG') {
            $this->getSGISIN();
            die('test');
        }

        $file = dirname(__FILE__) . '/ISIN/' . $ar[1] . '.php';

        // invalid / not supported
        if(!file_exists(dirname(__FILE__) . '/ISIN/' . $ar[1] . '.php')) {
            return false;
        }
        
        include $file;

        return isset($this->map[$stockCode]) ? $this->map[$stockCode] : false;
    }

    function getSGISIN() 
    {

        $ch = curl_init();
        $f = tmpfile();
        curl_setopt($ch, CURLOPT_URL, 'https://www.boerse-stuttgart.de/api/bsg-feature-navigation/Search/PostSearchInput');   
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
            http_build_query(array(
                'searchSubmit' => 'sow',
                'language' => 'en', 
                'datasource' => '5849b3c3-7bd3-4570-9fed-df92b0788426'
            ))
        );     
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/x-www-form-urlencoded"
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $str = curl_exec($ch);
        fseek($f,0);
        echo fread($f, 32*1024);
        fclose($f);
        curl_close($ch);


        var_dump(curl_getinfo($ch));
        var_dump("GET SG ISIN\n");
        var_dump($str);
        die('test');
    }
}
