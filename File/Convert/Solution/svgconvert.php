<?php


class File_Convert_Solution_svgconvert extends File_Convert_Solution
{
    var $cmd;
   
    static $rules = array(
        array(
         
            'from' =>    array( //source
                'image/svg',
            ),
            'to' =>    array( //target
                'application/pdf',
                 'image/png' ,
            )
        ),
        
          
    );
    
    function convert($fn,$x,$y,$pg) 
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
}