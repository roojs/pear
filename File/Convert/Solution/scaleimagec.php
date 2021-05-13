<?php

class File_Convert_Solution_scaleimagec extends File_Convert_Solution
{
    
   
    static $rules = array(
         
        
         
    );
       
    function targetName($fn, $x, $y)
    {
          
        return  $fn . '.'.$x.'c'.$y.'.' . $this->ext;
           
    }
     
    
    function convert($fn,$x,$y,$pg) 
    {
        // print_r(array('scaleimageC', func_get_args()));exit;
        if (empty($x) && empty($y)) {
            return false;
        }
        $ext = $this->ext;
        $target = $fn . '.'.$x.'c'.$y.'.' . $ext;
//        echo "GOT TARGET"  . $target;exit;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
//            echo "GOT TARGET"  . $target;exit;
            return $target;
        }
        
        
        $extent = '';
        $resize = '-resize';
        switch (true) { // what about fit/pad etc...
            
            // added to allow fix to 'x' without padding.. (empty string in x or y)
            case (empty($x) && !strlen($x)) :  // eg. ''x'123' (not 0x...)
                $scale = "x{$y}";
                break;
            case (empty($y) && !strlen($y)) :
                $scale = "{$x}x";
                break;
            
            case (empty($x)) :
                $scale = "x{$y}>";
                break;
            case (empty($y)) :
                $scale = "{$x}x>";
                break;
            
            // both x & y..
            default:
                
                $scale = "{$x}x{$y}^"; 
                $extent =" -gravity center -crop {$x}x{$y}+0+0";
                break;
        }
        require_once 'System.php';
        $CONVERT = System::which("convert");
        $targetName = $target;
        $strip = '-strip';
        if ($this->to == 'image/x-ms-bmp') {
            $targetName = "bmp3:$target";
            $strip = '';
        }
//         var_dump($CONVERT);exit;
         if ($CONVERT) {
            // note extend has to go after the resize.. so it does that first...
            $cmd = "{$CONVERT} $strip -colorspace sRGB -interlace none -density 300 -quality 90 ". 
                 " {$resize} '{$scale}' ". $extent  . " '{$fn}' '{$targetName}'";
             
             $cmdres  = $this->exec($cmd);
            $this->exec($cmd);

//            print_r($cmd);exit;
            
        } else {
            die("not supported yet...");
            // 100x0 --<< 100 SQUARE? = pad..
            // 100x   << 100 width proportion..
            // 100x200 << fit and pad.
            
             
            
            list($width, $height) = getimagesize($fn);
            
            
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
            $source = imagecreatefromjpeg($fn);
            // Resize
            //resource $dst_image , resource $src_image , 
                // int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h 
            imagecopyresampled($thumb, $source, $padx, $pady, 0, 0, $scalex, $scaley, $width, $height);

            imagejpeg($thumb,$target);
        }
        
        
//        echo $target;
//        exit;
       
        clearstatcache();
        return  file_exists($target)  && filesize($target) ? $target : false;
    }
}