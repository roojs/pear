<?php
/**
 * our moto... "To Convert and Serve"!!!
 * usage:
 * 
 * $x=  new File_Convert("filename", "application/pdf");
 * $fn = $x->convert("image/jpeg", 200, 0);
 * $x->serve('inline');
 * 
 * generic serve
 * $x=  new File_Convert("filename", "application/pdf");
 * $fn = $x->convert("application/pdf"); // does no conversion (as it's the same.
 * $x->serve('inline'); // can fix IE Mess...
 * 
 * 
 */
/*
// test: 
echo '<PRE>';
$x = new File_Convert(false);
print_r($x->getConvMethods('application/msword', 'application/pdf'));
print_r($x->getConvMethods('application/msword', 'image/jpeg'));
print_r($x->getConvMethods('application/acad', 'image/jpeg'));
var_dump($x->getConvMethods('application/acad', 'application/msword')); // impossible

*/


class File_Convert
{
   
    
    var $fn = ''; // filename
    var $mimetype = '';
    // for results..
    var $debug = false; // set to true to turn on deubgging
    var $to;
    var $target;
    var $lastaction = false;
    function File_Convert($fn, $mimetype) 
    {
        $this->fn = $fn;
        $this->mimetype = $mimetype;
    }
    
    
    function convert($toMimetype, $x= 0, $y =0) 
    {
         //echo '<PRE>'; print_r(array('convert', func_get_args()));
        if ($toMimetype != $this->mimetype) {
           
         
            
            $action = $this->getConvMethods($this->mimetype, $toMimetype);
            
            // echo '<PRE>';print_r($action);
            if (!$action) {
                return false;
            }
            $action->debug = $this->debug;
            $fn = $action->runconvert($this->fn, $x, $y);
            if (!$fn) {
                $this->to = $toMimetype;
                $this->lastaction = $action->last; // what failed.
                return false;
            }
        } else {
            $fn = $this->fn;
        }
        if (preg_match('#^image/#', $toMimetype) && ( !empty($x) || !empty($y))) {
            $sc = new File_Convert_Solution('scaleImage', $toMimetype, $toMimetype);
            $sc->debug= $this->debug;
            if (strpos($x, 'x')) {
                $bits = explode('x', $x);
                $x = (int)$bits[0];
                $y = empty($bits[0]) ?  0 : (int)$bits[0];;
            }
            $fn = $sc->runconvert($fn, (int)$x, (int)$y);
            
         
        }
        $this->target = $fn;
        $this->to = $toMimetype;
        return $fn;
        
        
    }
    
    function serve($type=false, $filename =false, $delete_after = false) /** may die **/
    {
        if (empty($this->target)) {
            // broken image? for images...
            $cmd = isset($this->lastaction->cmd) ? $this->lastaction->cmd : "No Method";
            die("not available in this format was: {$this->mimetype}, request: {$this->to}<BR>
                Running - $cmd");
        }
       
        if (!file_exists($this->target)) {
            die("file missing");
       }
       
        
        $fn = $this->target;
        $isIE = preg_match('#msie [0-9.]+#i', isset($_SERVER['HTTP_USER_AGENT']) ? isset($_SERVER['HTTP_USER_AGENT'])  : '');
        
        $ts = filemtime($fn);
        
        $ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? 
            stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;
        
        
        //if (empty($_REQUEST['ts']) && !$isIE && $ifModifiedSince && strtotime($ifModifiedSince) >= $ts) {
        //    header('HTTP/1.0 304 Not Modified');
        //    exit; // stop processing
       // }
        // ie kludge cause it's brain dead..
        
       // var_dump($isIE); 
        $mt =  $this->to;
        if ($isIE && preg_match('#^application/#i', $this->to)) {
            // pdfs' break if we add this line?
            //$mt = 'application/octet-stream' ;
            $type = $type === false ?  'attachment' : $type;
        }
        $type = $type === false ?  'inline' : $type;
        header('Content-type: '. $mt);
        //if (!preg_match('#^image\/#i', $this->to)) {
        header("Expires: ");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header("Pragma: public");     
        header("Last-Modified: " . gmdate("D, d M Y H:i:s",  $ts) . " GMT");

        //var_dump($mt);
        require_once 'File/MimeType.php';
        $fmt = new File_MimeType();
        $ext = $fmt->toExt($mt);
        $sfn = basename($fn);
        $sfn = preg_match('#\.'.$ext.'$#', $sfn) ? $sfn : $sfn. '.' .$ext;
        //var_dump($sfn);
        
        if (empty($filename)) {
            $filename = $sfn;
        }
        
        header('Content-length: '. filesize($fn));
       // if ($type != 'inline') {
            header('Content-Disposition: '.$type.'; filename="' . htmlspecialchars($sfn).  '"');
       // }
        
        if ($_SERVER["REQUEST_METHOD"] == 'HEAD') {
            //fclose($fh);
            exit;
        }
        $fh = fopen($fn, 'r');
        fpassthru($fh);
        fclose($fh);
        if ($delete_after) {
            unlink($fn);
        }
        exit;
        
        
    }
    
    
    var $methods =  array(
            array( 'unoconv',
                    array( // source
                        'application/msword',
                        'application/mswordapplication',
                        'application/vnd.oasis.opendocument.text',
                    ),    // targets
                    array( 
                        'application/msword',
                        'application/vnd.oasis.opendocument.text',
                        'application/pdf',
                        'text/html',
                    )
            ),
            array( 'ssconvertxls',
                array ('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ),
                array('application/vnd.ms-excel')
            ),
            
            array( 'html2text',
                array ('text/html' ),
                array('text/plain')
                
            ),
            
            array( 'unoconv',
                array( //source
                    
                    'application/vnd.ms-excel',
                    'application/vnd.oasis.opendocument.spreadsheet' ,
                    
                ),
                array( //target
                    'application/vnd.ms-excel',
                    'application/vnd.oasis.opendocument.spreadsheet' ,
                    'application/pdf',
                    'text/html',
                )
            ),
              
            array( 'unoconv',
                array( //source
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                ),
                array( //target
                    'application/pdf',
                )
            ),
            
            array( 'whtml2pdf',
                array(
                    'text/html',
                ),
                array(
                    'application/pdf',
                )
            ),
            array( 'text2html',
                array(
                    'message/rfc822',
                    'text/plain', 
                ),
                array(
                    'text/html',
                )
            ),
            array( 'acad2svg',
                array ('application/vnd.dwg',
                     'application/acad',
                     'application/x-acad',
                     'application/autocad_dwg',
                     'image/x-dwg',
                     'application/dwg',
                     'application/x-dwg',
                     'application/x-autocad',
                     'image/vnd.dwg',
                     'drawing/dwg',
                ),
                array(
                    'image/svg'
                )
            ),
            array( 'svg2pdf',
                array(
                    'image/svg',
                ),
                array(
                    'application/pdf',
                ),
            ),
            array( 'convert',
                array(
                    'image/jpeg',
                    'image/gif',
                    'image/png',
                ),
                array(
                    'image/jpeg',
                    'image/gif',
                    'image/png',
                    'image/x-ms-bmp',
                )
            ),
            array('convert800mp', // mulipage convert...
                array(
                    'application/pdf',
                    'application/tiff',
                ),
                array(
                    'image/jpeg',
                    'image/gif',
                    'image/png',
                )
            ),
            array('ffmpeg', // mulipage convert...
                array(
                    
                          
                    'video/avi',
                    'video/x-ms-wmv',
                    'video/mp4',
                    'video/x-msvideo',
                    'video/quicktime',
                ),
                array(
                    'image/jpeg',
                    
                )
            ),
            array( 'abiword',
                array( // source
                    'application/x-abiword'
                ),    // targets
                array( 
                    'application/msword'
                )
            ),
            
        ); 
    
    function getConvMethods($from, $to, $stack = array())
    {
            // these source types have to use unoconv....
        //print_r(array('getConvMethods', func_get_args()));
        // $pos[converter] => array( list of targets);
        
        if (count($stack) > 4) { // too deepp.. pos. recursion.
            return false;
        }
        $pos = array();
        
        foreach($this->methods as $t) {
            if (!in_array($from, $t[1])) {
                continue;
            }
            if (in_array($to,$t[2])) {
                return new File_Convert_Solution($t[0], $from, $to);  // found a solid match - returns the method.
            }
            $pos[$t[0]] = $t[2]; // list of targets
            
        }
        $stack[] = $from;
        $res = array();
        foreach($pos as $conv => $ar) {
            // array contains a list of pos. mimetypes.
            // 
            foreach($ar as $targ) {
                if ($from == $targ) {
                    continue; // skip going back...
                }
                if (in_array($targ, $stack)) {
                    continue; // going backwards..
                }
                // we need to build a list here. 
                
                
                
                $ns = $stack;
                $ns[] = $targ;
                $try = $this->getConvMethods($targ, $to, $ns);
                // try will be an array of method, from, to (or false)
                
                if ($try === false) {
                    continue; // mo way to convert
                }
                $first = new File_Convert_Solution($conv, $from, $targ);
                $sol_list= $first->add($try);
                
                $res[] = $sol_list;
                
            }
            
        }
        if (empty($res)) {
            return false;
        }
        // find the shortest..
        usort  ( $res  , array($this, 'solutionSort'));
        $best = $res[0];
        return $best;
        
        
    }
    function solutionSort($a, $b) {
        if ($a->count() == $b->count()) {
            return 0;
        }
        return $a->count() < $b->count() ?  -1 : 1;
    }
    
    
        
                
          
           
}

class File_Convert_Solution_Stack
{
    var $type = 1;
    var $list;
    var $debug = false;
    
    function count()
    {
        return count($this->list);
    }
    function runconvert($fn, $x, $y)
    {
        if ($this->debug) {
            echo "<PRE>RUNNING LIST<BR>";
        }
        foreach($this->list as $s) {
            $s->debug =$this->debug;
              
            $fn = $s->runconvert($fn, $x, $y);
            $this->last = $s;
            if (!$fn) {
                return $fn; // failure..
            }
        }
        return $fn;
    }
}

class File_Convert_Solution
{
    var $type = 0;
    var $method;
    var $from;
    var $to;
    var $ext;
    var $debug = false;
    function File_Convert_Solution($method, $from ,$to)
    {
        $this->method = $method;
        $this->from = $from;
        $this->to = $to;
        $this->last = $this;
        
    }
    function count()
    {
        return 1;
    }
    function add($in) 
    {
        $ret = new File_Convert_Solution_Stack();
        $ret->list[] = $this;
        if ($in->type == 0) {
            $ret->list[] = $in;
            return $ret;
        }
        $ret->list = array_merge($ret->list, $in->list);
        return $ret;
        
    }
    
    // convertion methods
    function runconvert($fn, $x=0,$y=0)
    {
        if (!file_exists($fn)) {
            $this->cmd = "ERROR:". $fn . " does not exist";
            return false;
        }
        require_once 'File/MimeType.php';
        $mt = new File_MimeType();
        $this->ext = $mt->toExt($this->to);
        if ($this->debug) {
            print_r(array('runconvert', func_get_args()));
            print_r($this);
        }
        
        if (!$this->ext) {
            return false;
        }
        $method = $this->method;
        return $this->$method($fn,$x,$y);
    }
    function unoconv($fn, $try=0) 
    {
        
        $ext = $this->ext;
        $target = $fn . '.' . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'System.php';
        $xvfb = System::which('xvfb-run');
        if (empty($xvfb)) {
            $this->cmd = "Missing xvfb";
        }
        $uno = System::which('unoconv');
        if (empty($xvfb)) {
            $this->cmd = "Missing unoconv";
            return false;
        }
        $cmd = "$xvfb -a  $uno -f $ext --stdout " . escapeshellarg($fn) . " 1> " . escapeshellarg($target);
        // echo $cmd;
        $res = `$cmd`;
        $this->cmd = $cmd . "\n"  . $res;
        clearstatcache();
        if (file_exists($target)  && filesize($target) < 400) {
            $this->cmd .= "\n" . filesize($target) . "\n" . file_get_contents($target);
            
            unlink($target);
            clearstatcache();
           // if ($try) {
                return false;
           // }
          //  sleep(20);
          //  return $this->unoconv($fn,1); // try again
            
        }
        
        return  file_exists($target) ? $target : false;
     
    }
    
    function ssconvertxls($fn) 
    {
        
        $ext = $this->ext;
        $target = $fn . '.' . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'System.php';
        $ssconvert = System::which('ssconvert');
        
        $cmd = "$ssconvert -I Gnumeric_Excel:xlsx -T Gnumeric_Excel:excel_biff8 " . escapeshellarg($fn) . " " . escapeshellarg($target);
        ///echo $cmd;
        `$cmd`;
        $this->cmd = $cmd;
        clearstatcache();
        
         return  file_exists($target)  && filesize($target) ? $target : false;
     
    }
    /**
     * html2text wrapper
     *
     * 
     * 
     * @param {String} $fn source_filename
     * @param {Array} $opt_ar  option arrray - supports 'width', 'style'
     */
    function html2text($fn, $opt_ar=array()) 
    {
        
        $ext = $this->ext;
        $target = $fn . '.' . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'System.php';
        $html2text= System::which('html2text');
        if (!$html2text) {
            // eak.
            die("html2text is not installed");
        }
        
        $opts = array();
        if (is_array($opt_ar) && isset($opt_ar['width'])) {
            $opts[] = '-width ' . ((int) $opt_ar['width']);
            
        }
        if (is_array($opt_ar) && isset($opt_ar['style'])) {
            $opts[] = '-style ' .  escapeshellarg($opt_ar['style']);
            
        }
        $cmd = "$html2text " . implode(' ', $opts)
            . " -o " . escapeshellarg($target) . "  " . escapeshellarg($fn);
        if ($this->debug) {
            echo $cmd ."\n";
        }
        `$cmd`;
        $this->cmd = $cmd;
        clearstatcache();
        
        return  file_exists($target)  && filesize($target) ? $target : false;
     
    }    
    function whtml2pdf($fn) 
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
        
        
        $cmd = $conv .' -n ' . escapeshellarg($fn) . ' ' .escapeshellarg($target);
        $this->cmd = $cmd;
       
        $res = `$cmd`;
        clearstatcache();
        
        if (!file_exists($target) ) {
            // try with X wrapper..Xvfb
        
            $xvfb = System::which('xvfb-run');
            if (empty($xvfb) || !file_exists($xvfb)) {
                return false;
            }
            $cmd = $xvfb .' ' . $cmd;
            $this->cmd = $cmd;
           // echo $cmd;
            $res = `$cmd`;
        }
        
        //echo $res;
        clearstatcache();
        return  file_exists($target)  && filesize($target) ? $target : false;
        
    }
    function text2html($fn)
    {
        
        $target = $fn . '.html';
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        $fh = fopen($target,'w');
        $fs = fopen($fn,'r');
        fwrite($fh, '<HTML><BODY><PRE style="white-space: pre-wrap;">');
        fwrite($fh, htmlspecialchars( fread($fs,4096)));
        fwrite($fh, '</PRE></BODY></HTML>');
        fclose($fs);
        fclose($fh);
        clearstatcache();
        
        return  file_exists($target)  && filesize($target) ? $target : false;
        
        
        
    }
    function ffmpeg($fn)
    {
        
         
        $ext = 'jpg';
        $target = $fn . '.' . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'System.php';
        $ffmpeg = System::which('ffmpeg');
        $cmd = "$ffmpeg  -ss 5  -i " .
                escapeshellarg($fn) ." -vcodec mjpeg -vframes 1 -an -f rawvideo -s 320x240 " . escapeshellarg($target);

        ///echo $cmd;
        `$cmd`;
        $this->cmd = $cmd;
        clearstatcache();
        
        return  file_exists($target)  && filesize($target) ? $target : false;
        
        
        
    }
    
    function abiword($fn)
    {
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
        $cmd = "$abiword   --to=" . escapeshellarg($target) . ' ' .escapeshellarg($fn);
        ///echo $cmd;
        `$cmd`;
        $this->cmd = $cmd;
        clearstatcache();
        
        return  file_exists($target)  && filesize($target) ? $target : false;
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
        `$cmd`;
        clearstatcache();
        return  file_exists($target)  && filesize($target) ? $target : false;
    }
    function svg2pdf($fn) 
    {
        
        
        $target = $fn . '.pdf';
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'System.php';
        $conv = System::which('rsvg-convert');
        if (!$conv) {
            echo "RSVG-CONVERT to available - install librsvg2-bin";
            return false;
        }
        $cmd = "$conv -f pdf -o " . escapeshellarg($target) . ' ' .escapeshellarg($svg);
        
        `$cmd`;
                    
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
        require_once 'System.php';
        $CONVERT = System::which("convert");
        $cmd = "$CONVERT -colorspace RGB -interlace none -density 300 ". 
                        "-quality 80  -resize '400x>' ". escapeshellarg($fn) . " " . escapeshellarg($target);
        
        `$cmd`;
        clearstatcache();
        return  file_exists($target)  && filesize($target) ? $target : false;
        
    }
    function convert800mp($fn, $x, $y)
    {
        
         $xscale = 400;
        if (!empty($x) && $x> $xscale ) {
            $xscale = $x;
        }
        $ext = $this->ext;
        $target = $fn . '-' . $xscale . '.' .  $ext;
        if (!$this->debug && file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'System.php';
        
        $density = $xscale > 800 ? 300: 75; 
        
        $CONVERT = System::which("convert");
        $cmd = "$CONVERT -colorspace RGB -interlace none -density $density ". 
                        "-quality 90  -resize '". $xscale . "x>' ". escapeshellarg($fn) . "[0] " . escapeshellarg($target);
        

        if ($this->debug) {
           echo "$cmd <br/>";
           
        }
       `$cmd`;
        $this->cmd = $cmd;
        clearstatcache();
        return  file_exists($target)  && filesize($target) ? $target : false;
        
    }
    function convert($fn) // image only..
    {
        
        
        $ext = $this->ext;
        $target = $fn . '.' . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'System.php';
        $CONVERT = System::which("convert");
        $cmd = "$CONVERT -colorspace RGB -interlace none -density 300 ". 
                        "-quality 80   ". escapeshellarg($fn) . " " . escapeshellarg($target);
        
        `$cmd`;
        clearstatcache();
        return  file_exists($target)  && filesize($target) ? $target : false;
        
    }
    function scaleImage($fn, $x, $y) 
    {
      //   print_r(array('scaleimage', func_get_args()));
        if (empty($x) && empty($y)) {
            return false;
        }
        $ext = $this->ext;
        $target = $fn . '.'.$x.'x'.$y.'.' . $ext;
        
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        $extent = '';
        switch (true) { // what about fit/pad etc...
            case (empty($x)) :
                $scale = "x{$y}>";
                break;
            case (empty($y)) :
                $scale = "{$x}x>";
                break;
            default: 
                $scale = "{$x}x{$y}>"; 
                $extent ="-extent '{$x}x{$y}>' -gravity center -background white -define jpeg:size={$x}x{$y}";
                break;
        }
        require_once 'System.php';
        $CONVERT = System::which("convert");
        if ($CONVERT) {
            // note extend has to go after the resize.. so it does that first...
            $cmd = "{$CONVERT}  -colorspace RGB -interlace none -density 300 -quality 80 ". 
                 " -resize '{$scale}' ". $extent  . " '{$fn}' '{$target}'";
             
             $cmdres  = `$cmd`;
            `$cmd`;
        } else {
            // handle x only!!!
            
            
            list($width, $height) = getimagesize($fn);
            
            if ( (empty($y) && $x > $width && $x >  $height)  || (!empty($y) && $x > $width && $y > $height)) {
                $newwidth =  $x;
                $newheight = empty($y) ? $x : $y;
                // pad..
                $padx = floor(($newwidth - $width) /2);
                $pady = floor(($newheight - $height) /2);
                
                $scalex = $width;
                $scaley = $height;
                
            } else {
            
                $percent = $x/$width;
                if (!empty($y)) {
                    $percent = min($percent, $y/$height);
                }
                $newwidth =  $x;
                $newheight = empty($y) ? $x : $y;
                
                $scalex = $width * $percent;
                $scaley = $height * $percent;
                
                $padx = floor(($newwidth - $scalex) /2);
                $pady = floor(($newheight - $scaley) /2);
                
                
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
        return  file_exists($target)  && filesize($target) ? $target : false;
    }
}





