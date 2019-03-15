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
    var $log  = array();
    var $solutions = array();
    
    function __construct($fn, $mimetype, $options=array())
    {
        $this->fn = $fn;
        $this->mimetype = $mimetype;
        self::$options = $options;
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
        require_once 'File/Convert/Solution.php';
        $sc = new File_Convert_Solution('scaleImage', $toMimetype, $toMimetype);
        //$sc->convert = $this;
        $sc->debug= $this->debug;
        $this->solutions[] = $sc;
            
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
        $pg = (int) $pg;
        
        if(empty($pg) || is_nan($pg * 1)){
            $pg = false;
        }
        
         //echo '<PRE>'; print_r(array('convert', func_get_args()));
        if (
                $toMimetype != $this->mimetype ||
                (
                        $toMimetype == $this->mimetype &&
                        $toMimetype == 'image/gif'
                )
        ) {

            $action = $this->getConvMethods($this->mimetype, $toMimetype);
            
            //echo '<PRE>';print_r($action);
            if (!$action) {
                $this->debug("No methods found to convert {$this->mimetype} to {$toMimetype}");
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
        if (preg_match('#^image/#', $toMimetype) && $toMimetype != 'image/gif' && ( !empty($x) || !empty($y))) {
            //var_dump(array($toMimetype));
            require_once 'File/Convert/Solution.php';

            $sc = new File_Convert_Solution(strpos($x, 'c')  !== false ? 'scaleImageC' : 'scaleImage' , $toMimetype, $toMimetype);
            $sc->debug= $this->debug;
            $this->solutions[] = $sc;
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
        
        $etag = md5($ts. '!' . $fn);
        
        $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ?
                trim($_SERVER['HTTP_IF_NONE_MATCH'],"'\"") : false;

        $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? 
            stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;
        
        $ts_string = gmdate("D, d M Y H:i:s",  $ts) . " GMT";
        
        if ((($if_none_match && $if_none_match == $etag) || (!$if_none_match)) &&
            ($if_modified_since && $if_modified_since == $ts_string))
        {
            header('HTTP/1.1 304 Not Modified');
            exit();
        }
        
        
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
    
        // a reasonable expiry time - 5 minutes..
        header("Expires: ". gmdate("D, d M Y H:i:s",  strtotime("NOW + 5 MINUTES")) . " GMT");
        header("Cache-Control: must-revalidate");
        header("Pragma: public");     
        header("Last-Modified: " . $ts_string . " GMT");
        header("ETag: \"{$etag}\"");
        
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
       
        // needs to be removed after debugging - otherwise it logs to error.log
        //ini_set('display_errors', 0); //trigger_error("Serving: {$this->target} ". filesize($fn));
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
            @ob_flush();
            flush();
        }
        
        fclose($fh);
        
        if ($delete_after) {
            unlink($fn);
        }
        exit;
        
        
    }
    
    
    
    
    
    static $methods =  array(
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
                    'image/png', //add it back for MediaOutreach
                    
                ),
            ),
            
            array( 'gifsicle',
                array( // source
                    'image/gif',
                ),    // targets
                array( 
                    'image/gif',
                )
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
            )
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
        require_once 'File/Convert/Solution.php';

        if (count($stack) > 4) { // too deepp.. pos. recursion.
            return false;
        }
        $pos = array();
        // print_r(self::$methods);
        foreach(self::$methods as $t) {
            if (!in_array($from, $t[1])) {
                continue;
            }
            if (in_array($to,$t[2])) {
                $ret =  new File_Convert_Solution($t[0], $from, $to);  // found a solid match - returns the method.
                //$ret->convert = $this; // recursion?
                $this->solutions[] = $ret;

                
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
                //$first->convert = $this;
                $sol_list= $first->add($try);
                
                $res[] = $sol_list;
                
            }
            
        }
        if (empty($res)) {
             $this->debug("No methods found to convert {$from} to {$to}");

            return false;
        }
        // find the shortest..
        usort  ( $res  , array($this, 'solutionSort'));
        $best = $res[0];
        $this->solutions[] = $best;
        return $best;
        
        
    }
    function solutionSort($a, $b) {
        if ($a->count() == $b->count()) {
            return 0;
        }
        return $a->count() < $b->count() ?  -1 : 1;
    }
    
    function debug($str)
    {
        if ($this->debug) {
            echo $string . "<br/>\n";
        }
        $this->log[] = $str;
    }
    
    
        
                
          
           
}






