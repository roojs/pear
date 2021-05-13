<?php
//?? still useds?

class File_Convert_Solution_convert800 extends File_Convert_Solution
{
    
   
    var $rules = array(
        array(
         
            'from' =>    array( //source
               
            ),
            'to' =>    array( //target
              
            )
        ),
        
 
    );
    function convert($fn) // might not be needed...
    {
        
        
        $ext = $this->ext;
        $target = $fn . '.' . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        
        $targetName = $target;
        $strip = '-strip';
        if ($this->to == 'image/x-ms-bmp') {
            $targetName = "bmp3:$target";
            $strip = '';
        }
        
        require_once 'System.php';
        $CONVERT = System::which("convert");
        $cmd = "$CONVERT $strip -colorspace sRGB -interlace none -density 300 ". 
                        "-quality 90 -resize '400x>' ". escapeshellarg($fn) . " " . escapeshellarg($targetName);
        
        $this->exec($cmd);
        clearstatcache();
        return  file_exists($target)  && filesize($target) ? $target : false;
        
    }
}