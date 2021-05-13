<?php
class File_Convert_Solution_convert800mp extends File_Convert_Solution
{
    
   
    var $rules = array(
        array(
         
            'from' =>    array( //source
                  'image/tiff',
            ),
            'to' =>    array( //target
               'image/jpeg',
                'image/gif',
                'image/png',
            )
        ),
        
 
    );
    function convert800mp($fn, $x, $y, $pg=false)
    {
        
        $xscale = 400;
        if (!empty($x) && $x> $xscale ) {
            $xscale = $x;
        }
        $ext = $this->ext;
        $target = $fn . '-' . $xscale . '.' .  $ext;
        if ($pg !== false) {
            $target = $fn . '-' . $xscale . '.pg'. $pg . '.' .  $ext;
        }
        if (!$this->debug && file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        
        $targetName = $target;
        $strip = '-strip';
        if ($this->to == 'image/x-ms-bmp') {
            $targetName = "bmp3:$target";
            $strip = '';
        }
        
        require_once 'System.php';
        
        $density = $xscale > 800 ? 300: 75; 
        
        $CONVERT = System::which("convert");
        $cmd = "$CONVERT $strip -colorspace sRGB -interlace none -density $density ". 
                        "-quality 90  -resize '". $xscale . "x>' "
                        . escapeshellarg($fn) . 
                        ($pg === false ? "[0] " : "[$pg] ") . 
                        escapeshellarg($targetName);
        

        
        $this->debug( $cmd);
           
        
        
        $this->exec($cmd);
        
        clearstatcache();
        $fe = file_exists($target)  && filesize($target) ? $target : false;
        if ($fe) {
            @chmod($target,fileperms($fn));
            
            return $target;
        }
         
        return false;
        
    }
    