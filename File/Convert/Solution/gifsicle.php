<?php


class File_Convert_Solution_gifsicle extends File_Convert_Solution
{
    
   
    static $rules = array(
        array(
         
            'from' =>    array( //source
                'image/gif',
            ),
            'to' =>    array( //target
                'image/gif',
            )
        ),
         
            
    );
    function convert($fn, $x, $y) 
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