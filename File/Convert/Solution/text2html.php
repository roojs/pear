 <?php

class File_Convert_Solution_text2html extends File_Convert_Solution
{
     
 
    
    static $rules = array(
        array(
         
            'from' =>    array( //source
                 'message/rfc822',
                    'text/plain', 
            ),
            'to' =>    array( //target
                'text/html',
            )
        ),
    );
    
            
   
    function convert($fn)
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
}