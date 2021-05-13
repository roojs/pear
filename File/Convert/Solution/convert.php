<?php
 
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
    function convert($fn) // image only..
    {
        
        $frame = '';
        $ext = $this->ext;
        $target = $fn . '.' . $ext;
        
        $this->debug("COVERT: FE:" . (file_exists($target) ? 1: 0) );
        $this->debug("COVERT: FS:" . (file_exists($target) ?  (filemtime($target) . '>' .  filemtime($fn)) : 'n/a'));
        
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        $flat = '';
        $targetName = $target;
        if ($this->to == 'image/jpeg' && $this->from != 'image/gif') {
            $flat = " -background '#ffffff' --flatten ";
        }
        $strip = '-strip';
        if ($this->to == 'image/x-ms-bmp') {
            $targetName = "bmp3:$target";
            $strip = '';
        }
        if ($this->from == 'image/gif') {
            $frame = '[0]';
        }
        
        require_once 'System.php';
        $CONVERT = System::which("convert");
        $cmd = "$CONVERT " . $strip .  "  -colorspace sRGB -interlace none -density 800 $flat ". 
                        "-quality 90   ". escapeshellarg($fn . $frame) . " " . escapeshellarg($targetName );
         $this->debug($cmd);
        $this->exec($cmd);
        clearstatcache();
        $fe = file_exists($target)  && filesize($target) ? $target : false;
        if (!$fe) {
            return false;
        }
        
        @chmod($target,fileperms($fn));
            
        return $target;
    }