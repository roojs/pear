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

$x = new File_Convert(file, 'app../excel',array('sheet'=>array(0,1,2...) ));
$out = $x->convert('text/csv');
*/

class File_Convert
{
   
    
    static $options = array();
    
    var $fn = ''; // filename
    var $mimetype = '';
    // for results..
    var $debug = false; // set to true to turn on deubgging
    var $to;
    var $target;
    var $lastaction = false;
    function File_Convert($fn, $mimetype, $options=array())
    {
        $this->fn = $fn;
        $this->mimetype = $mimetype;
        $this->options = $options;
     }
    
    
    function convertExists($toMimetype, $x= 0, $y =0) 
    {
        
        if ($toMimetype != $this->mimetype) {
            $action = $this->getConvMethods($this->mimetype, $toMimetype);
            
            // echo '<PRE>';print_r($action);
            if (!$action) {
                return false;
            }
            $fn = $action->convertExists($this->fn, $x, $y);
        } else {
            $fn = $this->fn;
        }
        if (!$fn) {
            return false;
        }
        if (!preg_match('#^image/#', $toMimetype) || ( empty($x) && empty($y))) {
            return $fn;
        }
        //echo "testing scale image";
        
        $sc = new File_Convert_Solution('scaleImage', $toMimetype, $toMimetype);
        $sc->convert = $this;
        $sc->debug= $this->debug;
            
        if (strpos($x, 'x')) {
            $bits = explode('x', $x);
            $x = (int)$bits[0];
            $y = empty($bits[1]) ?  0 : (int)$bits[1];;
        }
          
            
        $fn = $sc->convertExists($fn, (int)$x, (int)$y);
             
        
        //$this->target = $fn;
        //$this->to = $toMimetype;
        return $fn;
    }
    /**
     *
     * actually run the convertion routine.
     * 
     */
    
    function convert($toMimetype, $x= 0, $y =0, $pg=false) 
    {
         //echo '<PRE>'; print_r(array('convert', func_get_args()));
        if ($toMimetype != $this->mimetype) {

            $action = $this->getConvMethods($this->mimetype, $toMimetype);
            
            //echo '<PRE>';print_r($action);
            if (!$action) {
                return false;
            }
            $action->debug = $this->debug;
            $fn = $action->runconvert($this->fn, $x, $y, $pg);
            if (!$fn) {
                $this->to = $toMimetype;
                $this->lastaction = $action->last; // what failed.
                return false;
            }
            
            // let's assume that conversions can handle scaling??
            
            
        } else {
            $fn = $this->fn;
        }
//        if(!strpos($x, 'c')){
//            print_r('inin?');
//            print_r($x);
//            print_r(' > ');
//            print_r($y);exit;
//            
////            $size = explode('c', $x);
//        }
//        print_r(strpos($x, 'c'));
//            print_r(' > ');
//            print_r($y);exit;
//        print_r($this->fn);exit;
        if (preg_match('#^image/#', $toMimetype) && ( !empty($x) || !empty($y))) {
            //var_dump(array($toMimetype));
            
            $sc = new File_Convert_Solution(strpos($x, 'c')  !== false ? 'scaleImageC' : 'scaleImage' , $toMimetype, $toMimetype);
            $sc->debug= $this->debug;
            
            $x  = str_replace('c', 'x', $x);
            
            if (strpos($x, 'x') !== false ) {
                $bits = explode('x', $x);
                $x = $bits[0];
                $y = !is_numeric($bits[1]) ?  '' : (int)$bits[1];
            }
            $x = strlen($x) ? (int) $x : '';
            $y = strlen($y) ? (int) $y : '';
//            print_r($x);
//            print_r(' > ');
//            print_r($y);exit;
            $fn = $sc->runconvert($fn,  $x, $y, $pg);
             
        }
//        print_r($this->target);
        $this->target = $fn;
        $this->to = $toMimetype;
        return $fn;
        
        
    }
    /**
     * Serve the file to a browser so it can be downloaded, or viewed.
     *
     * @param type string      attachment or inline..
     * @param filename string  name of file
     * @param delete_after boolean (false)   delete file after sending..
     *
     */
    function serve($type=false, $filename =false, $delete_after = false) // may die **/
    {
        if (empty($this->target)) {
            // broken image? for images...
            $cmd = isset($this->lastaction->cmd) ? $this->lastaction->cmd : "No Method";
            die("not available in this format was: {$this->mimetype}, request: {$this->to}<BR>
                Running - $cmd");
        }
        clearstatcache();
        if (!file_exists($this->target))
        {
            trigger_error("Target does not exist: {$this->target}");
            print_r($this->target);
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
            header('Content-Disposition: '.$type.'; filename="' . htmlspecialchars($filename).  '"');
       // }
        ini_set('display_errors', 0);
        trigger_error("Serving: {$this->target} ". filesize($fn));
        if ($_SERVER["REQUEST_METHOD"] == 'HEAD') {
            //fclose($fh);
            exit;
        }
        
        
        $fh = fopen($fn, 'rb');
        //fpassthru($fh);
        
        // passthrough seems to have problems -- trying fread
        while(!feof($fh))
        {
            echo @fread($fh, 1024*8);
            ob_flush();
            flush();
        }
        
        fclose($fh);
        
        if ($delete_after) {
            unlink($fn);
        }
        exit;
        
        
    }
    
    
    
    
    
    var $methods =  array(
            array( 'unoconv', //FIXME run this 3 times??
                    array( // source
                  //      'text/html', /// testing..
                        'application/msword',
                        'application/mswordapplication',
                        'application/vnd.oasis.opendocument.text',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ),    // targets
                    array( 
                        'application/msword',
                        'application/vnd.oasis.opendocument.text',
                        'application/pdf',
                        'text/html',
                    )
            ),
            
            array ( 'abitodocx',
                    array( // source
                  //      'text/html', /// testing..
                        'application/x-abiword',
                        
                    ),    // targets
                    array( 
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
//                        'application/msword',
//                        'application/mswordapplication',
                    )
            ),
//            
//            array ( 'word2pdf',
//                    array( // source
//                        'application/msword',
//                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
//                    ),    
//                    array( // targets
//                        'application/pdf',
//                    )
//            ),
            array( 'ssconvertxls',
                array (
                       'application/vnd.ms-excel',
                       'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                       ),
                array(
                      'application/vnd.ms-excel',
                      'text/csv',
                      'text/xml'
                      )
            ),
            
            array( 'html2text',
                array ('text/html' ),
                array('text/plain')
                
            ),
            
            array( 'unoconv',//FIXME run this 3 times??
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
              
            array( 'unoconv',//FIXME run this 3 times??
                array( //source
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                ),
                array( //target
                    'application/pdf',
                )
            ),
            array( 'm4a2mp3',
                array( //source
                    'audio/mp4',
                ),
                array( //target
                    'audio/mpeg',
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
            array( 'svgconvert',
                array(
                    'image/svg',
                ),
                array(
                    'application/pdf',
                   // 'image/png', //<< this is a mess - do not enable it... - let the sytem do it to pdf.. then onto whatever..
                    
                ),
            ),
            array( 'convert',
                array(
                    'image/jpeg',
                    'image/gif',
                    'image/png'
                    
                ),
                array(
                    'image/jpeg',
                    'image/gif',
                    'image/png',
                    'image/x-ms-bmp',
                    'image/tiff',
                    'application/pdf'
                )
            ),
            
            array('pdftocairo', // mulipage convert... was pdftoppn
                array(
                    'application/pdf',
               //     'application/tiff',
                ),
                array(
                    'image/jpeg',
                //    'image/gif',
                    'image/png',
                )
            ),
            
            array('convert800mp', // mulipage convert...
                array(
                  //  'application/pdf',
                    'image/tiff',
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
                    'video/mpeg',
                    'video/quicktime',
                ),
                array(
                    'image/jpeg',
                    
                )
            ),
            array( 'abiword',
                array( // source
                    'text/html',
                    'application/x-abiword'
                ),    // targets
                array( 
                    'application/rtf',
                    'application/vnd.oasis.opendocument.text',
                    'application/x-abiword',
                    'application/mswordapplication'
                )
            ),
            
        ); 
    /**
     * This recursively calls to find the best match.
     * First by matching the 'from'
     *
     * Then if multiple outputs are available,
     * It will see if any of those can be used to generate the to, by recurivly calling it'self..
     *
     */
     
    function getConvMethods($from, $to, $stack = array())
    {
            // these source types have to use unoconv....
        //print_r(array('getConvMethods', func_get_args()));
        // $pos[converter] => array( list of targets);
        
        if (count($stack) > 4) { // too deepp.. pos. recursion.
            return false;
        }
        $pos = array();
//        print_r($this->methods);
        foreach($this->methods as $t) {
            if (!in_array($from, $t[1])) {
                continue;
            }
            if (in_array($to,$t[2])) {
                $ret =  new File_Convert_Solution($t[0], $from, $to);  // found a solid match - returns the method.
                $ret->convert = $this;
                return $ret;
            }
            // from matches..
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
//                print_r($conv);exit;
                $first = new File_Convert_Solution($conv, $from, $targ);
                $first->convert = $this;
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
    function runconvert($fn, $x, $y, $pg=false)
    {
        if ($this->debug) {
            echo "<PRE>RUNNING LIST<BR>";
        }
        foreach($this->list as $s) {
            $s->debug =$this->debug;
              
            $fn = $s->runconvert($fn, $x, $y, $pg=false);
            $this->last = $s;
            if (!$fn) {
                return $fn; // failure..
            }
        }
        return $fn;
    }
    
    function convertExists($fn, $x, $y)
    {
        
        foreach($this->list as $s) {
           
              
            $fn = $s->convertExists($fn, $x, $y);
            if (!$fn) {
                return false;
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
    var $convert; // reference to caller..
    
    function File_Convert_Solution($method, $from ,$to)
    {
        $this->method = $method;
        $this->from = $from;
        $this->to = $to;
        $this->last = $this;
        
    }
    
    function exec($cmd)
    {
        if ($this->debug) {
            echo "$cmd\n";
        }
        $ret = `$cmd`;
        if ($this->debug) {
            echo "$cmd\n";
        }
        $this->cmd = $cmd ."\n" . $ret;
        
        return $ret;
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
    function runconvert($fn, $x=0,$y=0, $pg=false)
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
    
    //FIXME this method run 3 times??
    function unoconv($fn, $try=0) 
    {
        
        $ext = $this->ext;
        
        
        $target = str_replace('.', '_', $fn) . '.' . $ext;
        
        
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'System.php';
        $xvfb = System::which('xvfb-run');
        if (empty($xvfb)) {
            $this->cmd = "Missing xvfb";
            return false;
        }
        $uno = System::which('unoconv');
        if (empty($uno)) {
            $this->cmd = "Missing unoconv";
            return false;
        }
        // before we used stdout -- not sure why.
        //$cmd = "$xvfb -a  $uno -f $ext --stdout " . escapeshellarg($fn) . " 1> " . escapeshellarg($target);
        $cmd = "$xvfb -a  $uno -f $ext -o " . escapeshellarg($target) . " " . escapeshellarg($fn);
        ////  echo $cmd;
        $res = $this->exec($cmd);
        
        clearstatcache();
        
//        print_R($target);
//        print_r("--------\n");
//        var_dump(file_exists($target));
//        var_dump(is_dir($target));
       
        
        if (is_dir($target)) {
            // it's an old version of unoconv.
            $tmp = '/tmp/temp_pdf';
            if(!is_dir($tmp)){
                mkdir($tmp);
            }
            
            
            $dir = scandir($target, 1);
            
//            print_r($dir);
            
            $filename = $dir[0];
            $file = $target.'/'.$filename;
            
            copy($file, $tmp.'/'.$filename);
            
            
            unlink($target.'/'.$filename);
            rmdir($target);
            
            copy($tmp.'/'.$filename, $target);
            
//            exit;
//            create temporary directory 
//            use scandir($target)[0]; to find first file
//            move it to the temporary directory
//            delete the target
//            move the new file to the target
            
            clearstatcache();
        }
        
//         exit;
        if (!file_exists($target) || (file_exists($target)  && filesize($target) < 400)) {
            //$this->cmd .= "\n" . filesize($target) . "\n" . file_get_contents($target);
            
            // try again!!!!
            @unlink($target);
            clearstatcache();
            sleep(3);
            
            $res = $this->exec($cmd);
            clearstatcache();
        
            
        }
        
//        print_r($target);
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
         if (!$ssconvert) {
            // eak.
            die("ssconvert is not installed");
        }
        
        $format = 'UNKNOWN'; ///?? error condition.
        
        switch($this->from) {
            
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                $from = 'Gnumeric_Excel:xlsx';
                break;
            
            case 'application/vnd.ms-excel':
                $from = 'Gnumeric_Excel:excel';
                break;
            
            default:
                 die("ssconvert used on unknown format:" . $this->from);
            
        }
        
        switch($this->to) {
            
            case 'application/vnd.ms-excel':
                $format = 'Gnumeric_Excel:excel_biff8';
                break;
            
            case 'text/csv':
                $format = 'Gnumeric_stf:stf_csv';
                break;
            
            case 'text/xml':
                $format = 'Gnumeric_XmlIO:sax';
                break;
            
            default:
                 die("ssconvert used on unknown format:" . $this->to);
        }
        
        $ssconvert_extra = '';
        $sheet = false;
        if (isset($this->convert->options['sheet'])) {
            $sheet = $this->convert->options['sheet'];
            $ssconvert_extra = ' -S ';
        }
        
        $xvfb = System::which('xvfb-run');
        if (empty($xvfb) || !file_exists($xvfb)) {
              $cmd = "$ssconvert $ssconvert_extra  -I $from -T $format " .
                escapeshellarg($fn) . " " .
                escapeshellarg($target);
        } else {
             $cmd = "$xvfb $ssconvert $ssconvert_extra  -I $from -T $format " .
                escapeshellarg($fn) . " " .
                escapeshellarg($target);
        }
        
       
        ///echo $cmd;
        $this->exec($cmd);
        
        clearstatcache();
        
        if ($sheet !== false) {
            $b = basename($fn);
            $d = dirname($fn);
            
            if (file_exists($d)) {
                
                $list = glob($fn . '.' . $ext . '.*');
                foreach($list as $l){
                    $ll = $l;
                    $s = array_pop(explode('.', $ll));
                    if(in_array($s, $sheet)){
                        continue;
                    }
                    
                    unlink($l);
                    
                }
            }
            
            $target = $fn;
        }
        
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
            . "-utf8 -o " . escapeshellarg($target) . "  " . escapeshellarg($fn);
        if ($this->debug) {
            echo $cmd ."\n";
        }
        $this->exec($cmd);
        
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
        
        if (!empty(File_Convert::$options['wkhtmltopdf.bin'])) {
            $conv = System::which(File_Convert::$options['wkhtmltopdf.bin']);
        }
        
        if (!empty(File_Convert::$options['wkhtmltopdf'])) {
            $conv .= File_Convert::$options['wkhtmltopdf'];
        }
        
        $cmd = $conv .' -n ' . escapeshellarg($fn) . ' ' .escapeshellarg($target);
        
        $res = $this->exec($cmd);
        clearstatcache();
        
        if (!file_exists($target) ) {
            // try with X wrapper..Xvfb
        
            $xvfb = System::which('xvfb-run');
            if (empty($xvfb) || !file_exists($xvfb)) {
                return false;
            }
            $cmd = $xvfb .' ' . $cmd;
            
            $res = $this->exec($cmd);
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
        $cmd = "$ffmpeg   -i " .
                escapeshellarg($fn) ." -vcodec mjpeg -vframes 1 -an -f rawvideo -ss 20  -s 320x240 " . escapeshellarg($target);

        ///echo $cmd;
        $this->exec($cmd);
        
        clearstatcache();
        
        return  file_exists($target)  && filesize($target) ? $target : false;
        
        
        
    }
    
    function abiword($fn)
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
//        echo $cmd;exit;
        $this->exec($cmd);
       
        clearstatcache();
        
        return  file_exists($target)  && filesize($target) ? $target : false;
    }
    
    function abitodocx($fn)
    {
        require_once 'File/MimeType.php';
        $fmt = new File_MimeType();
        $fext = $fmt->toExt($this->from);
        
        $ext = $this->ext;
        $target = str_replace('.', '_', $fn) . '.' . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'File/Convert/AbiToDocx.php';
        $conv = new File_Convert_AbiToDocx($fn);
        $conv->save($target); 
        
        return  file_exists($target)  && filesize($target) ? $target : false;
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
    
    
    function pdftoppm($fn, $x, $y, $pg=false)
    {
        $xscale = 400; // min size?
        if (!empty($x) && $x> $xscale ) {
            $xscale = $x;
        }
        
        
        $ext = $this->ext; //'png'; //$this->ext;
        
        $target = $fn . '-' . $xscale . '.' .  $ext;
        if ($pg !== false) {
            $target = $fn . '-' . $xscale . '-pg'. $pg . '.' .  $ext;
        }
        $finaltarget = $target ; //. ($this->ext == 'png' ?  '' : '.jpeg');
        
        
        if (!$this->debug && file_exists($finaltarget)  && filesize($finaltarget) && filemtime($finaltarget) > filemtime($fn)) {
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
        if ($this->debug) {
           echo "$cmd <br/>";
           
        }
        
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
        
        
        if (!$this->debug && file_exists($finaltarget)  && filesize($finaltarget) && filemtime($finaltarget) > filemtime($fn)) {
            return $finaltarget;
        }
        require_once 'System.php';
        
        
        
        // revised version using pdfinfo and pdftoppm
        
        $PDFINFO = System::which("pdfinfo");
        $GREP = System::which("grep");
        $STRINGS= System::which("strings");
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
        if ($this->debug) {
           echo "$cmd <br/>";
           
        }
        
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
        

        if ($this->debug) {
           echo "$cmd <br/>";
           
        }
        
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
        
        
        $ext = $this->ext;
        $target = $fn . '.' . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        $flat = '';
        $targetName = $target;
        if ($this->to == 'image/jpeg') {
            $flat = " -background '#ffffff' --flatten ";
        }
        $strip = '-strip';
        if ($this->to == 'image/x-ms-bmp') {
            $targetName = "bmp3:$target";
            $strip = '';
        }
        
        require_once 'System.php';
        $CONVERT = System::which("convert");
        $cmd = "$CONVERT " . $strip .  "  -colorspace sRGB -interlace none -density 800 $flat ". 
                        "-quality 90   ". escapeshellarg($fn) . " " . escapeshellarg($targetName );
         if ($this->debug) {
           echo "$cmd <br/>";
           
        }
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
        
        
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            
            return $target;
        }
        $targetName = $target;
        $strip = '-strip';
        if ($this->to == 'image/x-ms-bmp') {
            $targetName = "bmp3:$target";
            $strip = '';
        }
        
        //echo "GOT TARGET"  . $target;
        
        list($width, $height) = getimagesize($fn);
        
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
                $scale = "{$x}x{$y}>"; 
                $extent ="-extent '{$x}x{$y}>' -gravity center -background white -define jpeg:size={$x}x{$y}";
                break;
        }
        require_once 'System.php';
        $CONVERT = System::which("convert");
        
         //var_dump($CONVERT);
         if ($CONVERT) {
            // note extend has to go after the resize.. so it does that first...
            $cmd = "{$CONVERT} " . $strip . " -colorspace sRGB -interlace none -density 800 -quality 90 ". 
                 " -resize '{$scale}' ". $extent  . " '{$fn}' '{$targetName}'";
             
             $cmdres  = $this->exec($cmd);
            $this->exec($cmd);
            
            
            
        } else {
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
//        print_r(array('scaleimage', func_get_args()));exit;
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
                 " -resize '{$scale}' ". $extent  . " '{$fn}' '{$targetName}'";
             
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
}





