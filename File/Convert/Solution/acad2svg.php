<?php
// not sure if this even works..

class File_Convert_Solution_acad2svg extends File_Convert_Solution
{
     
 
    
    var $rules = array(
        array(
         
            'from' =>    array( //source
                   'text/html',
                    'application/x-abiword'
            ),
            'to' =>    array( //target
                'application/rtf',
                'application/vnd.oasis.opendocument.text',
                'application/x-abiword',
                'application/mswordapplication'
            )
        ),
    );   
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