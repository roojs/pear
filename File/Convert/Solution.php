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
    
   
     
    
    function word2pdf($fn)
    {
        
    }
    
    function acad2svg($fn)
    {
            
       
        $target = $fn . '.svg';
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
         $cad2svg = escapeshellcmd(realpath(dirname(__FILE__).'/../install/cad2svg'));
        if (!is_executable($cad2svg)) {
            echo "CAD2SVG not executable - fix it";
            return false;
        }
   
        $cmd = "$cad2svg -o " . escapeshellarg($target) . ' ' .escapeshellarg($fn);
       // echo $cmd;
        $this->exec($cmd);
        clearstatcache();
        return  file_exists($target)  && filesize($target) ? $target : false;
    }
    function svgconvert($fn, $x, $y) 
    {
        
        switch($this->to) {
            case 'application/pdf';
                $ext = '.pdf';
                $cvt = 'pdf';
                break;
            case 'image/png';
                $ext = '.png';
                $cvt = 'png';
                break;
            
                
        }
        $opts = '';
        if (!empty($x)) {
            $opts .= ' -w '. ((int) $x);
        }
        if (!empty($x)) {
            $opts .= ' -h '. ((int) $y);
        }
        
        $target = $fn . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'System.php';
        $conv = System::which('rsvg-convert');
        if (!$conv) {
            echo "RSVG-CONVERT to available - install librsvg2-bin";
            return false;
        }
        // white background - if you need transparancy -- use another tool?
        
        $cmd = "$conv --background-color=white  -f $cvt -o " . escapeshellarg($target) . ' ' .escapeshellarg($fn);
        
        $this->exec($cmd);
        
        clearstatcache();
         
        
        return  file_exists($target)  && filesize($target) ? $target : false;
    }
    function convert800($fn) // might not be needed...
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
    /**
     * This is the 'failback' version if pdfcario is not installed...
     *
     */
    
    function pdftoppm($fn, $x, $y, $pg=false)
    {
        $xscale = 400; // min size?
        if (!empty($x) && $x> $xscale ) {
            $xscale = $x;
        }
        
        
        $ext = 'png'; /// older versions only support PNG.  $this->ext; //'png'; //$this->ext;
        
        $target = $fn . '-' . $xscale . '.' .  $ext;
        if ($pg !== false) {
            $target = $fn . '-' . $xscale . '-pg'. $pg . '.' .  $ext;
        }
        $finaltarget = $target ; //. ($this->ext == 'png' ?  '' : '.jpeg');
        
        
        if ($this->debug < 2 && file_exists($finaltarget)  && filesize($finaltarget) && filemtime($finaltarget) > filemtime($fn)) {
            
            $this->debug("using existing image - $finaltarget");
        
            return $finaltarget;
        }
        require_once 'System.php';
        
        
        // revised version using pdfinfo and pdftoppm
        
        $PDFINFO = System::which("pdfinfo");
        $GREP = System::which("grep");
        $STRINGS= System::which("strings");
        // needs strings if starngs chars are in there..
        $cmd = $PDFINFO . ' '. escapeshellarg($fn) . " | $STRINGS | $GREP 'Page size'";
         
        
         $info = trim( $this->exec($cmd));
        $match = array();
        // very presumtiuos...
       
       //print_R($info);
        if (!preg_match("/([0-9.]+)[^0-9]+([0-9.]+)/",$info, $match)) {
            $this->cmd .= " could not find 0-0 in the return string";
            return false;
        }
        
        $yscale =  floor( ($match[2] / $match[1]) * $xscale) * 3;
        $xscale = floor($xscale) * 3;
        $pg = ($pg === false) ? 1 : $pg;
        
        
        // older versions only support png...
        
//        print_r($xscale);
//        print_r($yscale);
        
        $PDFTOPPM = System::which("pdftoppm");
        if (!$PDFTOPPM) {
            echo "pdftoppm to available - install poppler-utils";
            return false;
            
        }
        $cmd = "$PDFTOPPM -f $pg " 
                    . "-l $pg  " 
                    //. "-png "
                    . "-r 1200 "
//                    . "-rx 1200 "
//                    . "-ry 1200 "
                    . '-' . $ext . " "
                    . " -scale-to-x {$xscale} " 
                    . " -scale-to-y {$yscale} " 
                    .  escapeshellarg($fn) . " " 
                    . escapeshellarg($fn.'-conv');
        
        // expect this file..
//        echo "$cmd <br/>";exit;
        $this->debug(  $cmd); 
        
        $res = $this->exec($cmd);
        $this->result = $res;
        
        clearstatcache();
        // for some reason this makes 01 or 1?
        $out = $fn . sprintf('-conv-%d.'.str_replace('e', '', $ext) , $pg);
        
        $fe = file_exists($out)  && filesize($out) ? $out : false;
        if ($fe) {
            rename($out, $target);
            
            @chmod($target,fileperms($fn));
            
            return $target;
            
            
            print_R('in?');exit;
            //FIXME never fun this???
            $ret = $this->ext == 'png' ? $target: $this->convert($target);
            @chmod($ret,fileperms($fn));
            
            return $ret;
        }
        $out = $fn . sprintf('-conv-%02d.'.str_replace('e', '', $ext), $pg);
        //$out = $fn . '-conv-01.jpg';
        
        $fe = file_exists($out)  && filesize($out) ? $out : false;
        if ($fe) {
             rename($out, $target);
            @chmod($target,fileperms($fn));
            return $target;
            
            print_R('in?');exit;
            $ret = $this->ext == 'png' ? $target: $this->convert($target);
            @chmod($ret,fileperms($fn));
            return $ret;
        }
        
        $out = $fn . sprintf('-conv-%03d.'.str_replace('e', '', $ext), $pg);
        //$out = $fn . '-conv-001.jpg'; .. if more than 100 pages...
        
        $fe = file_exists($out)  && filesize($out) ? $out : false;
        if ($fe) {
             rename($out, $target);
            @chmod($target,fileperms($fn));
            return $target;
            
            print_R('in?');exit;
            
            //$ret = $this->ext == 'png' ? $target: $this->convert($target);
            @chmod($ret,fileperms($fn));
            return $ret;
        }
        
        
        
        
        return  false;
        
    }
    
      // new version - but does not appear to work that well..
    
    function pdftocairo($fn, $x, $y, $pg=false)
    {
        
        $xscale = 600; // min size?
        if (!empty($x) && $x> $xscale ) {
            $xscale = $x;
        }
        
        
        $ext = $this->ext; //'png'; //$this->ext;
        
        $target = $fn . '-' . $xscale . '.' .  $ext;
        if ($pg !== false) {
            $target = $fn . '-' . $xscale . '-pg'. $pg . '.' .  $ext;
        }
        $finaltarget = $target ; //. ($this->ext == 'png' ?  '' : '.jpeg');
        
        $this->debug("final target check - $finaltarget ");
        $this->debug("FE: " . (file_exists($finaltarget)  ? 1 : 0));
        $this->debug("FS0: " . (file_exists($finaltarget) && filesize($finaltarget) ? 1  : 0));
        $this->debug("FS: " . (file_exists($finaltarget) ? (filemtime($finaltarget) . ">" . filemtime($fn)) : 'n/a'));
                     
        if ($this->debug < 2 && file_exists($finaltarget)  && filesize($finaltarget) && filemtime($finaltarget) > filemtime($fn)) {
            $this->debug("final target exists - $finaltarget - skipping");
            return $finaltarget;
        }
        require_once 'System.php';
        
        
        
        // revised version using pdfinfo and pdftoppm
        
        $PDFINFO = System::which("pdfinfo");
        $this->debug("PDFINFO: $PDFINFO");
        $GREP = System::which("grep");
        $this->debug("GREP: $GREP");
        $STRINGS= System::which("strings");
        $this->debug("PDFINFO: $STRINGS");
        // needs strings if starngs chars are in there..
        $cmd = $PDFINFO . ' '. escapeshellarg($fn) . " | $STRINGS | $GREP 'Page size'";
         
//        var_dump($cmd);exit;
        $info = trim( $this->exec($cmd));
        $match = array();
        // very presumtiuos...
       
       
        if (!preg_match("/([0-9.]+)[^0-9]+([0-9.]+)/",$info, $match)) {
            $this->cmd .= " could not find 0-0 in the return string";
            return false;
        }
        
        $yscale =  floor( ($match[2] / $match[1]) * $xscale) * 3;
        $xscale = floor($xscale) * 3;
        $pg = ($pg === false) ? 1 : $pg;
        
        
        // older versions only support png...
        
//        print_r($xscale);
//        print_r($yscale);
        
        $PDFTOPPM = System::which("pdftocairo");
        if (!$PDFTOPPM) {
            $this->debug("NO PDFTOCAIRO trying pdftoppm");
            return $this->pdftoppm($fn,$x,$y, $pg);
            
        }
        $cmd = "$PDFTOPPM   -f $pg " 
                    . "-l $pg  " 
                    //. "-png "
                    . "-r 300 " // was 1200?
//                    . "-rx 1200 "
//                    . "-ry 1200 "
                    . '-' . $ext . " "
                    . " -scale-to-x {$xscale} " 
                    . " -scale-to-y {$yscale} " 
                    .  escapeshellarg($fn) . " " 
                    . escapeshellarg($fn.'-conv');
        
        // expect this file..
//        echo "$cmd <br/>";exit;
         
        $res = $this->exec($cmd);
        $this->result = $res;
        
        clearstatcache();
        // for some reason this makes 01 or 1?
        $out = $fn . sprintf('-conv-%d.'.str_replace('e', '', $ext) , $pg);
        
        $fe = file_exists($out)  && filesize($out) ? true : false;
        if ($fe) {
            $this->debug("GOT conv file: renaming $out to $target");
            rename($out, $target);
            
            @chmod($target,fileperms($fn));
            
            return $target;
            
            
            print_R('in?');exit;
            //FIXME never fun this???
            $ret = $this->ext == 'png' ? $target: $this->convert($target);
            @chmod($ret,fileperms($fn));
            
            return $ret;
        }
        $out = $fn . sprintf('-conv-%02d.'.str_replace('e', '', $ext), $pg);
        //$out = $fn . '-conv-01.jpg';
        
        $fe = file_exists($out)  && filesize($out) ? true : false;
        if ($fe) {
            $this->debug("GOT conv file: renaming $out to $target");
             rename($out, $target);
            @chmod($target,fileperms($fn));
            return $target;
            
            print_R('in?');exit;
            $ret = $this->ext == 'png' ? $target: $this->convert($target);
            @chmod($ret,fileperms($fn));
            return $ret;
        }
        
        $out = $fn . sprintf('-conv-%03d.'.str_replace('e', '', $ext), $pg);
        //$out = $fn . '-conv-001.jpg'; .. if more than 100 pages...
        
        $fe = file_exists($out)  && filesize($out) ? $out : false;
        if ($fe) {
            $this->debug("GOT conv file: renaming $out to $target");
             rename($out, $target);
            @chmod($target,fileperms($fn));
            return $target;
            
            print_R('in?');exit;
            
            //$ret = $this->ext == 'png' ? $target: $this->convert($target);
            @chmod($ret,fileperms($fn));
            return $ret;
        }
        
        $this->debug("Could not find OUTPUT FROM pdftocairo");
        
        
        return  false;
        
    }
    
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
    
    function m4a2mp3($fn){
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
    
    function gifsicle($fn, $x, $y) 
    {
        $x  = str_replace('c', 'x', $x);
        
        if (strpos($x, 'x') !== false ) {
            $bits = explode('x', $x);
            $x = $bits[0];
            $y = !is_numeric($bits[1]) ?  '' : (int)$bits[1];
        }
        $x = strlen($x) ? (int) $x : '';
        $y = strlen($y) ? (int) $y : '';
            
        $ext = $this->ext;
        
        $flat = '';
        
        
        $target = $fn . '.' . $ext;
        
        if (!empty($x)) {
            $target = $fn . '.' . $x. '.' . $ext;
            $flat = "--resize-width {$x}";
        }
        
        if (!empty($y)) {
            $target = $fn . '.' . $y. '.' . $ext;
            $flat = "--resize-height {$y}";
        }
        
        if (!empty($x) && !empty($y)) {
            $target = $fn . '.' . $x . 'x' . $y . '.' . $ext;
            $flat = "--resize-fit {$x}x{$y}";
        }
        
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        
        require_once 'System.php';
        $gifsicle = System::which("gifsicle");
        
        $cmd = "{$gifsicle} {$flat} {$fn} -o {$target}";
        
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
}