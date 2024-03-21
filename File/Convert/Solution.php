<?php

class File_Convert_Solution
{
    var $type = 0;
    var $from;  // mimetype
    var $to; // mimetype
    var $ext;  // target extension
    
    var $debug = 0;
    var $last = '';
    var $log = array();
    var $target = false;
    var $result = '';
    
    var $cmd;
    
    function __construct(  $from ,$to)
    {
         
        $this->from = $from;
        $this->to = $to;
        
        require_once 'File/MimeType.php';
        $mt = new File_MimeType();
        $this->ext = $mt->toExt($this->to);

        self::$options = $options;
         
        //$this->last = $this; //?? used where?
        
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
    
    
    function exec($cmd)
    {
        $this->debug("EXEC: $cmd");
        $ret = `$cmd`;
        $this->debug("RETURNED:  $ret");
        $this->cmd = $cmd ."\n" . $ret;
        return !is_string($ret) ? '' : $ret;
    }
    
    function count() // ??? why!?
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
    function runconvert($fn, $x=0, $y=0, $pg=false)
    {
        if (!file_exists($fn)) {
            $this->cmd = "ERROR:". $fn . " does not exist";
            return false;
        }
        if (!$this->ext) {
            die("missing mimetype");
             
        }
        
        $this->debug("runconvert : {$fn}, {$x}, {$y}, {$pg}");
  
        
        return $this->convert($fn, $x, $y, $pg);
    }
    
    function targetName($fn,$x,$y)
    {
        return $fn .'.'. $this->ext;
    }
    
    
    function convertExists($fn, $x, $y)
    {
         
        if (!$this->ext) {
            return false;
        }
        $fn = $this->targetName($fn, $x, $y);
     
        return file_exists($fn) ? $fn : false;
         
    }
    
    function convert($fn,$x,$y,$pg) {
        die("Convert not implemented for " . get_class($this));
    }
   
   
   
    static $deleteOnExit = false;
    /**
     * generate a tempory file with an extension (dont forget to delete it)
     */
    
    function deleteOnExitAdd($name)
    {
        if (self::$deleteOnExit === false) {
            self::$deleteOnExit  = array();
            register_shutdown_function(array('File_Convert_Solution','deleteOnExit'));
            
        }
        self::$deleteOnExit[] = $name;
    }
    
    function tempName($ext, $deleteOnExit=false)
    {
        
        $x = tempnam(ini_get('session.save_path'), HTML_FlexyFramework::get()->appNameShort.'TMP');
        unlink($x);
        $ret = $x .'.'. $ext;
        if ($deleteOnExit) {
            $this->deleteOnExitAdd($ret);
        }
        return $ret;
    
    }
   
    static function deleteOnExit()
    {
        if (count(func_get_args())) {
            trigger_error("Call deleteOnExitAdd ?!?");
        }
        foreach(self::$deleteOnExit as $fn) {
            if (file_exists($fn)) {
                unlink($fn);
            }
        }
    }
    function which($n)
    {
        require_once 'System.php';
        return System::which($n);
    }
     
    
}