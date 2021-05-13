<?php

class File_Convert_Solution
{
    var $type = 0;
    var $from;
    var $to;
    var $ext;
    var $debug = 0;
    var $last = '';
    var $log = array();
    
    function __construct($method, $from ,$to)
    {
         
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
        switch(getClass($this)) {
            case 'File_Convert_Solution_scaleimage':
                $target = $fn . '.'.$x.'x'.$y.'.' . $ext;
            case 'File_Convert_Solution_scaleimagec':
                $target = $fn . '.'.$x.'c'.$y.'.' . $ext;
            default:
                $target = $fn .'.'. $ext;
        }
        return file_exists($fn) ? $fn : false;
        
        
    }
    
   
     
    
}