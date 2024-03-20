<?php
require_once 'File/Convert/Solution.php';

class File_Convert_Solution_scaleimage extends File_Convert_Solution
{
    
   var $cmd;
    static $rules = array(
         
        
         
    );
      
    
    function targetName($fn, $x, $y)
    {
          
        return  $fn . '.'.$x.'x'.$y.'.' . $this->ext;
        
         
    }
     
    function convert($fn,$x,$y,$pg) 
    {
        
          clearstatcache();
        if (!file_exists($fn) || (empty($x) && empty($y))) {
            
            return false;
        }
        $ext = $this->ext;
        $target = $fn . '.'.$x.'x'.$y.'.' . $ext;
        
        $this->debug("COVERT: FE:" . (file_exists($target) ? 1: 0) );
        $this->debug("COVERT: FS:" . (file_exists($target) ?  (filemtime($target) . '>' .  @filemtime($fn)) : 'n/a'));
 
        if ($this->debug < 2 && file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            $this->debug("SCALEIMAGE - image exists $target");
            return $target;
        }
        $targetName = $target;
        $strip = '-strip';
        if ($this->to == 'image/x-ms-bmp') {
            $targetName = "bmp3:$target";
            $strip = '';
        }
        
        //echo "GOT TARGET"  . $target;
        
        list($width, $height) = @getimagesize($fn);
       
        $extent = '';
        switch (true) { // what about fit/pad etc...
            
            // added to allow fix to 'x' without padding.. (empty string in x or y)
            case (empty($x) && !strlen($x)) :  // y only
                $scale = "x{$y}";
               
                break;
            
            
            case (empty($y) && !strlen($y)) : // x only
                $scale = "{$x}x";
                //print_R(array($x,$width));
                
                break;
            
            case (empty($x)) :
                $scale = "x{$y}>";
                 if ($y == $height) { // no need to change
                    return $fn;
                }
                
                break;
            case (empty($y)) :
                $scale = "{$x}x>";
              
                if ($x == $width) {  // no need to change
                    return $fn;
                }
                
                
                break;
            default:
                
                list($width, $height) = @getimagesize($fn);
                 
                $scale = "{$x}x{$y}>";
                $define = "-define jpeg:size={$x}x{$y}";
                // if image required is bigger than the original - then we will just pad it..
                if ($width < $x && $height < $y) {
                    $scale = '';
                    $define  = '';
                }
                $extent ="-extent '{$x}x{$y}>' -gravity center -transparent {$define}";
                break;
        }
        
        require_once 'System.php';
        $CONVERT = System::which("convert");
        
          if ($CONVERT) {
            // note extend has to go after the resize.. so it does that first...
            // changed to using 'sample' rather than resize
            //-- it's alot faster? - not sure about quality though?
            // 5Mb is the cut off to use the faster version.
            $resize_method = @filesize($fn) > 50000000 ? '-sample' : '-scale';
            
            $cmd = "{$CONVERT} " . $strip . " -colorspace sRGB -interlace none -density 800 -quality 90 ". 
                 (strlen($scale) ?  " {$resize_method} '{$scale}' " : '' ).
                 $extent  . " '{$fn}' '{$targetName}'";
             $cmdres  = $this->exec($cmd);
            $this->exec($cmd);
            
            
            
        } else {
            // 100x0 --<< 100 SQUARE? = pad..
            // 100x   << 100 width proportion..
            // 100x200 << fit and pad.
            
             
            
            list($width, $height) = @getimagesize($fn);
            
            
            $pad = is_numeric($x) && is_numeric($y);
           
            if (!$pad) {
                if ($x) {
                    $newwidth = $x;
                    $newheight = ($x/$width ) * $height;
                } else {
                    $newwidth = ($y/$height) * $width;
                    $newheight = $y;
                }
                $padx= 0;
                $pady = 0;
                $scalex = $newwidth;
                $scaley = $newheight;
                
            } else {
                
                 
            
            
            
                if ( (empty($y)  && $x > $width && $x >  $height)
                    || (!empty($y)  && $x > $width && $y > $height)) {
                    
                    // larger with padding..
                    
                    
                    $newwidth =  $x;
                    $newheight = empty($y) ? $x : $y;
                    // pad..
                    $padx = floor(($newwidth - $width) /2);
                    $pady = floor(($newheight - $height) /2);
                    
                    $scalex = $width;
                    $scaley = $height;
                    
                } else {
                    
                    // smaller or without padding..
                    
                    
                    $percent = $x/$width;
                    $newwidth =  $x;
                    $newheight = empty($y) ? $x : $y;
                    
                    if (!empty($y)) {
                        $percent = min($percent,   $y/$height);
                    }
                    
                    $scalex = $width * $percent;
                    $scaley = $height * $percent;
                    
                    $padx = floor(($newwidth - $scalex) /2);
                    $pady = floor(($newheight - $scaley) /2);
                    
                    
                }
            }
            
            
            //echo '<PRE>';print_r(array(  'x' => $x, 'y' => $y,  'newwidth' => $newwidth , 'newheight' => $newheight , 'width' => $width , 'height' => $height ,
            //    'scalex' => $scalex , 'scaley' => $scaley ,  'padx' => $padx,  'pady' => $pady ));
            //exit;
            $thumb = imagecreatetruecolor($newwidth, $newheight);
            $white = imagecolorallocate ( $thumb , 255, 255, 255);

            imagefill($thumb, 0,0,  $white);
            
            switch(exif_imagetype($fn)) {
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($fn);
                    break;
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($fn);
                    break;
                default:
                    die("invalid image type");
                    
            }
            // Resize
            //resource $dst_image , resource $src_image , 
                // int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h 
            imagecopyresampled($thumb, $source, $padx, $pady, 0, 0, $scalex, $scaley, $width, $height);

            imagejpeg($thumb,$target);
        }
        
        
         // echo $cmd;          exit;
       
        clearstatcache();
        $fe =   file_exists($target)  && filesize($target) ? $target : false;
        
        if (!$fe) {
            return false;
        }
        @chmod($target,fileperms($fn));
        return $fe;
        
    }
}