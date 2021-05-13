<?php

class File_Convert_Solution
{
    var $type = 0;
    var $method;
    var $from;
    var $to;
    var $ext;
    var $debug = 0;
    var $last = '';
    var $log = array();
    
    function __construct($method, $from ,$to)
    {
        
        $this->method = $method;
        $this->from = $from;
        $this->to = $to;
        //$this->last = $this; //?? used where?
        
    }
    function debug($str)
    {
        if ($this->debug) {
            echo $str . "<br/>\n";
        }
        $this->log[] = $str;
    }
    
    
    function exec($cmd)
    {
        $this->debug($cmd);
        
        $ret = `$cmd`;
        
        $this->debug( $ret);
    
        $this->cmd = $cmd ."\n" . $ret;
        
        return $ret;
    }
    
    function count()
    {
        return 1;
    }
    function add($in) 
    {
        require_once 'File/Convert/SolutionStack.php';
        $ret = new File_Convert_SolutionStack();
        $ret->list[] = $this;
        if ($in->type == 0) {
            $ret->list[] = $in;
            return $ret;
        }
        $ret->list = array_merge($ret->list, $in->list);
        return $ret;
        
    }
    
    // convertion methods
    function runconvert($fn, $x=0,$y=0, $pg=false)
    {
        if (!file_exists($fn)) {
            $this->cmd = "ERROR:". $fn . " does not exist";
            return false;
        }
        require_once 'File/MimeType.php';
        $mt = new File_MimeType();
        $this->ext = $mt->toExt($this->to);
        
        $this->debug(print_r(array('runconvert', func_get_args()), true));
       // $this->debug(print_r($this,true));
                
        if (!$this->ext) {
            return false;
        }
        $method = $this->method;
        
        return $this->$method($fn, $x, $y, $pg);
    }
    
    
    function convertExists($fn, $x,$y)
    {
        
        if (!file_exists($fn)) {
            return false;
        }
        require_once 'File/MimeType.php';
        $mt = new File_MimeType();
        $ext = $mt->toExt($this->to);
        if (!$ext) {
            return false;
        }
//        print_r('in?'); exit;
        switch($this->method) {
            case 'scaleImage':
                $target = $fn . '.'.$x.'x'.$y.'.' . $ext;
            case 'scaleImageC':
                $target = $fn . '.'.$x.'c'.$y.'.' . $ext;
            default:
                $target = $fn .'.'. $ext;
        }
        return file_exists($fn) ? $fn : false;
        
        
    }
    
   
     
     
    function scaleImage($fn, $x, $y) 
    {
       
        
        //  print_r(array('scaleimage', func_get_args()));
        if (empty($x) && empty($y)) {
            return false;
        }
        $ext = $this->ext;
        $target = $fn . '.'.$x.'x'.$y.'.' . $ext;
        
        $this->debug("COVERT: FE:" . (file_exists($target) ? 1: 0) );
        $this->debug("COVERT: FS:" . (file_exists($target) ?  (filemtime($target) . '>' .  filemtime($fn)) : 'n/a'));
       
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
                $extent ="-extent '{$x}x{$y}>' -gravity center -background white {$define}";
                break;
        }
        require_once 'System.php';
        $CONVERT = System::which("convert");
        
         //var_dump($CONVERT);
         if ($CONVERT) {
            // note extend has to go after the resize.. so it does that first...
            // changed to using 'sample' rather than resize
            //-- it's alot faster? - not sure about quality though?
            // 5Mb is the cut off to use the faster version.
            $resize_method = filesize($fn) > 50000000 ? '-sample' : '-scale';
            
            $cmd = "{$CONVERT} " . $strip . " -colorspace sRGB -interlace none -density 800 -quality 90 ". 
                 (strlen($scale) ?  " {$resize_method} '{$scale}' " : '' ).
                 $extent  . " '{$fn}' '{$targetName}'";
            //var_dump($cmd);exit;
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
            $source = imagecreatefromjpeg($fn);
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
    
    function scaleImageC($fn, $x, $y) 
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