<?php

class File_Convert_SolutionStack
{
    var $type = 1;
    var $list;
    var $debug = false;
    var $last = '';
    
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
