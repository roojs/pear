<?php

/**
     * html2text wrapper
     *
     * 
     * 
     * @param {String} $fn source_filename
     * @param {Array} $opt_ar  option arrray - supports 'width', 'style'
     */
class File_Convert_Solution_Html2text extends File_Convert_Solution {
     
    var $sources =  array( // source
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );
     var $targets = array(
        'application/vnd.ms-excel',
        'text/csv',
        'text/xml'
    );
    
    function convert($fn, $opt_ar=array() 
    {
     
        
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
            
        $this->debug( $cmd );
        
        
        $this->exec($cmd);
        
        clearstatcache();
        
        return  file_exists($target)  && filesize($target) ? $target : false;
     
    }    