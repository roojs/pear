<?php
 
class File_Convert_Solution_m4a2mp3 extends File_Convert_Solution
{
    
   
    var $rules = array(
        array(
         
            'from' =>    array( //source
                 'image/jpeg',
                'image/gif',
                'image/png'
            ),
            'to' =>    array( //target
                'image/jpeg',
                'image/gif',
                'image/png',
                'image/x-ms-bmp',
                'image/tiff',
            )
        ),
        
   
    );
    function convert($fn){
        //print_r($fn);
        
        $ext = 'mp3';
        $target = $fn . '.' . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'System.php';
        $FAAD = System::which("faad");
        $LAME = System::which("lame");
        
        $cmd = "$FAAD -o - ".escapeshellarg($fn)." | $LAME - {$target}";
        
        ///echo $cmd;
        $this->exec($cmd);
       
        clearstatcache();
        
        return  file_exists($target)  && filesize($target) ? $target : false;
        
    }
}