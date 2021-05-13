<?php

class File_Convert_Solution_UnoConv extends File_Convert_Solution
{
    var $sources =  array( // source
        //      'text/html', /// testing..
          'application/msword',
          'application/mswordapplication',
          'application/vnd.oasis.opendocument.text',
          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      );
                    // targets
    var $targets = array( 
        'application/msword',
        'application/vnd.oasis.opendocument.text',
        'application/pdf',
        'text/html',
    );



    function convert($fn) 
    {
        
        // need a remove version for this..
        
        $target = $fn . '.pdf';
        
        // should check dates...!!!!
        if (file_exists($target)  && filesize($target) ) {
            
            if (is_file($fn) && filemtime($target) > filemtime($fn)) {
                return $target;
            }
            
        }
        require_once 'System.php';
        
        $conv = System::which('wkhtmltopdf');
        
        if (!empty(File_Convert::$options['wkhtmltopdf.bin'])) {
            $conv = System::which(File_Convert::$options['wkhtmltopdf.bin']);
            if (!$conv) {
                die("could not find ". File_Convert::$options['wkhtmltopdf.bin']);
            }
        }
        
        if (!empty(File_Convert::$options['wkhtmltopdf'])) {
            $conv .= File_Convert::$options['wkhtmltopdf'];
             
        }
        
        
        
        $cmd = $conv .' -n ' . escapeshellarg($fn) . ' ' .escapeshellarg($target);
        
        $res = $this->exec($cmd);
        clearstatcache();
        
        if (!file_exists($target) ) {
            // try with X wrapper..Xvfb
        
            $xvfb = System::which('xvfb-run');
            if (empty($xvfb) || !file_exists($xvfb)) {
                return false;
            }
            $cmd = $xvfb .' ' . $cmd;
            
            $res = $this->exec($cmd);
        }
        
        //echo $res;
        clearstatcache();
        return  file_exists($target)  && filesize($target) ? $target : false;
        
    }
}