<?php

/**
     * html2text wrapper
     *
     * 
     * 
     * @param {String} $fn source_filename
     * @param {Array} $opt_ar  option arrray - supports 'width', 'style'
     */
class File_Convert_Solution_html2text extends File_Convert_Solution {
     
     
    static $rules = array(
        array(
         
            'from' =>    array( //source
               'text/html' 
            ),
            'to' =>    array( //target
                'text/plain'
            )
        ),
         
            
    ); 
     
    
    function convert($fn,$x,$y,$pg) 
    {
        $opt_ar = $x; // ???
        
        $ext = $this->ext;
        $target = $fn . '.' . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'System.php';
        $html2text= System::which('html2text');
        if (!$html2text) {
            // eak.
            die("html2text is not installed");
        }
        
        $opts = array();
        if (is_array($opt_ar) && isset($opt_ar['width'])) {
            $opts[] = '-width ' . ((int) $opt_ar['width']);
            
        }
        if (is_array($opt_ar) && isset($opt_ar['style'])) {
            $opts[] = '-style ' .  escapeshellarg($opt_ar['style']);
            
        }
        $cmd = "$html2text " . implode(' ', $opts)
            . "-utf8 -o " . escapeshellarg($target) . "  " . escapeshellarg($fn);
        var_dump($cmd);
        die('test');

        $this->debug( $cmd );
        
        
        $this->exec($cmd);
        
        clearstatcache();
        
        return  file_exists($target)  && filesize($target) ? $target : false;
     
    }
}