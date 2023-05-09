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
 * options 
 * {
 *   delete_all : delete all the generated files after script execution when we call convert()
 * }
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
        
        if (!file_exists($fn)) {
            throw new Exception("Source file does not exist:". $fn );
        }
        
        $this->mimetype = $mimetype;
        self::$options = $options;
    }
    
    
    
    /**
     * check if conversion exists, and return the filename if it does.
     *
     */
    
    function convertExists($toMimetype, $x= 0, $y =0) 
    {
        static $actions = array();
        $fn = $this->fn;
        if ($toMimetype != $this->mimetype) {
            
            if (!isset($actions["{$this->mimetype} => $toMimetype"])) {
                $actions["{$this->mimetype} => $toMimetype"] = $this->getConvMethods($this->mimetype, $toMimetype);;
            }
                
            $action = $actions["{$this->mimetype} => $toMimetype"];
            
           
            // echo '<PRE>';print_r($action);
            if (!$action) {
                return false;
            }
            if (!file_exists($this->fn)) {
                return false;
            }
            
            $fn = $action->convertExists($this->fn, $x, $y);
            
        }
       
        if (!$fn) {
            return false;
        }
        
        if (!preg_match('#^image/#', $toMimetype) || ( empty($x) && empty($y))) {
            return $fn;
        }
        
        //echo "testing scale image";
        require_once 'File/Convert/Solution/scaleimage.php';
        $sc = new File_Convert_Solution_scaleimage($toMimetype, $toMimetype);
        //$sc->convert = $this;
        $sc->debug= $this->debug;
        $this->solutions[] = $sc;
            
        if (strpos($x, 'x')) {
            $bits = explode('x', $x);
            $x = (int)$bits[0];
            $y = empty($bits[1]) ?  0 : (int)$bits[1];;
        }
          
        if (!file_Exists($fn)) {
            return false;
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
        //print_R(func_get_args());
        if ($toMimetype == 'image/jpg') {
            $toMimetype = 'image/jpeg';
        }
        
        $pg = (int) $pg;
         if(empty($pg) || is_nan($pg * 1)){
            $pg = false;
        }
        $fn = $this->fn;
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
            // delete the generated files after script execution
            if(!empty(self::$options['delete_all'])) {
                $this->deleteOnExitAdd($fn);
            }

            if (!$fn) {
                $this->to = $toMimetype;
                $this->lastaction = $action->last ? $action->last : $action; // what failed.
                return false;
            }
            
            // let's assume that conversions can handle scaling??
            
            
        }  
             

        if (preg_match('#^image/#', $toMimetype) && $toMimetype != 'image/gif' && ( !empty($x) || !empty($y))) {
            //var_dump(array($toMimetype));
               
            require_once 'File/Convert/Solution.php';
            $scf = (strpos($x, 'c')  !== false ? 'scaleimagec' : 'scaleimage' );
            require_once 'File/Convert/Solution/'. $scf . '.php';
            $scls = 'File_Convert_Solution_' . $scf;
                
            $sc = new $scls($toMimetype, $toMimetype);
            $sc->debug=  $this->debug;
            $this->solutions[] = $sc;
            $x  = str_replace('c', 'x', $x);
            
            if (strpos($x, 'x') !== false ) {
                $bits = explode('x', $x);
                $x = $bits[0];
                $y = !is_numeric($bits[1]) ?  '' : (int)$bits[1];
            }
            $x = strlen($x) ? (int) $x : '';
            $y = strlen($y) ? (int) $y : '';
            //print_r($x); print_r(' > '); print_r($y);exit;
            
            $fn = $sc->runconvert($fn,  $x, $y, $pg);

            // delete the generated files after script execution
            if(!empty(self::$options['delete_all'])) {
                $this->deleteOnExitAdd($fn);
            }
          
        }
//        print_r($this->target);
        $this->target = $fn;
        $this->to = $toMimetype;
        return $fn;
        
        
    }
    
    function serveOnly($type=false, $filename =false, $delete_after = false)
    {
        $this->target = $this->fn;
        $this->to = $this->mimetype;
        $this->serve($type, $filename , $delete_after );
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
                Running - $cmd\n" . print_r(is_object($this->lastaction) ? $this->lastaction->log : '',true));
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
        // print_r($filename);
        // print_r(urlencode($filename));
        // print_r(str_replace('.', '%2E', str_replace('_', '%5F', str_replace('+', '%20', urlencode($filename)))));
        // exit;
        
        header('Content-length: '. filesize($fn));
       // if ($type != 'inline') {
            // header('Content-Disposition: '.$type.'; filename="' . htmlspecialchars($filename).  '"');
            header('Content-Disposition: '.$type.'; ' .
            // 'filename="' . htmlspecialchars($filename).  '"; ' . 
            // 'filename*="UTF-8\'\'' . str_replace('+', '%20', urlencode($filename)).  '";'
            "filename=\"EURRO rates\"; " .
            "filename*=utf-8''%e2%82%ac%20rates"
        );
       // }
       
        // needs to be removed after debugging - otherwise it logs to error.log
        //ini_set('display_errors', 0); //trigger_error("Serving: {$this->target} ". filesize($fn));
        if ($_SERVER["REQUEST_METHOD"] == 'HEAD') {
            //fclose($fh);
            exit;
        }
        //var_dump($fn, $mt); exit;
        header('Content-type: '. $mt);
        
        // even though we have done a file_exists above - it still errors out here occausionally.
        $fh = @fopen($fn, 'rb');
        //fpassthru($fh);
        
        // passthrough seems to have problems -- trying fread
        while($fh && !feof($fh))
        {
            echo @fread($fh, 1024*8);
            @ob_flush();
            flush();
        }
        
        if ($fh) {
            fclose($fh);
        }
        
        if ($delete_after) {
            @unlink($fn);
        }
        exit;
        
        
    }
    /**
     * 
     * returned format:
     *
     * (
          from =>
          to =>
          cls => instance of class
     )
     * 
     *
     */
    
    function methods()
    {
        static $methods = false;
        if ($methods !== false ) {
            return $methods;
        }
        $methods = array();
        $base = __DIR__.'/Convert/Solution';
        $dh = opendir($base);
        while (false !== ($fn = readdir($dh))) {
            if (substr($fn,0,1) == '.' ) {
                continue;
            }
            require_once 'File/Convert/Solution/' . $fn;
            $cls = 'File_Convert_Solution_'. str_replace('.php', '',$fn);
            
            $ref = new ReflectionClass($cls);        
            $val = $ref->getStaticPropertyValue('rules');
            
            foreach($val as $r) {
                $r['cls'] = $cls;
                $methods[] = $r;
            }
            
            
        }
        return $methods;
        
        
        
    }
    
    
    
 
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
        foreach($this->methods() as $t) {
            if (!in_array($from, $t['from'])) {
                continue;
            }
            if (in_array($to,$t['to'])) {
                $cls = $t['cls'];
                $ret =  new $cls($from, $to);  // found a solid match - returns the method.
                //$ret->convert = $this; // recursion?
                $this->solutions[] = $ret;

                //echo "got match?";
                return $ret;
            }
            // from matches..
            $pos[$t['cls']] = $t['to']; // list of targets
            
        }
        //echo "got here?";
        
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

                $first = new $conv($from, $targ);
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
            
            if (is_callable($this->debug)) {
                call_user_func($this->debug,$str);
            } else {
                echo $str . "<br/>\n";
            }
        }
        $this->log[] = $str;
    }
    
    static $deleteOnExit = false;
    /**
     * generate a tempory file with an extension (dont forget to delete it)
     */
    
    function deleteOnExitAdd($name)
    {
        if (self::$deleteOnExit === false) {
            register_shutdown_function(array('File_Convert','deleteOnExit'));
            self::$deleteOnExit  = array();
        }
        self::$deleteOnExit[] = $name;
    }
    
    static function deleteOnExit()
    {
        
        foreach(self::$deleteOnExit as $fn) {
            if (file_exists($fn)) {
                unlink($fn);
            }
        }
    }
                
          
           
}






