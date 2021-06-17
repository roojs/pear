<?php

class File_Convert_Solution_abiword extends File_Convert_Solution
{
     
 
    
    static $rules = array(
        array(
         
            'from' =>    array( //source
                   'text/html',
                    'application/x-abiword'
            ),
            'to' =>    array( //target
                'application/rtf',
                'application/vnd.oasis.opendocument.text',
                'application/x-abiword',
                'application/mswordapplication',
                'application/msword'
            )
        ),
    );   
    
    function convert($fn,$x,$y,$pg)
    {
        require_once 'File/MimeType.php';
        $fmt = new File_MimeType();
        $fext = $fmt->toExt($this->from);
        
        $ext = $this->ext;
        $target = str_replace('.', '_', $fn) . '.' . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'System.php';
        $abiword= System::which('abiword');
        if (empty($abiword)) {
            $this->cmd = "Missing abiword";
            return false;
        }
        $cmd = "$abiword  --import-extension=$fext --to=" . escapeshellarg($target) . ' ' .escapeshellarg($fn);
        
        $this->exec($cmd);
       
        clearstatcache();
        
        return  file_exists($target)  && filesize($target) ? $target : false;
    }
}