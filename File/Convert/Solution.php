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
    
    function __construct(  $from ,$to)
    {
         
        $this->from = $from;
        $this->to = $to;
        
        require_once 'File/MimeType.php';
        $mt = new File_MimeType();
        $this->ext = $mt->toExt($this->to);
         
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
            return false;
        }
        
        $this->debug(print_r(array('runconvert', func_get_args()), true));
  
        
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
         var_dump($fn);
        return file_exists($fn) ? $fn : false;
         
    }
    
    function convert($fn,$x,$y,$pg) {
        die("Convert not implemented for " . get_class($this));
    }
   
     
    
}