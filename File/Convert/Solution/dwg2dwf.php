<?php
/**
 * https://www.autodwg.com/download/documents/DWG-to-DWF-Command-line-User-Guide.pdf
 * demo does not work on command line - but can be sued to test.
 
  
 
 * xhost + (as me)
 * sudo su www-data
 * export DISPLAY=:0.0
 * wine AcmeCADConverter.exe.exe

 
*/
class File_Convert_Solution_dwg2dwf extends File_Convert_Solution
{
    
    static $rules = array(
     
        array(
         
            'from' =>    array( //source
                'application/vnd.dwg',
                'application/acad',
                'application/x-acad',
                'application/autocad_dwg',
                'image/x-dwg',
                'application/dwg',
                'application/x-dwg',
                'application/x-autocad',
                'image/vnd.dwg',
                'drawing/dwg'
            ),
            'to' =>    array( //target
                'drawing/dwf'// can do quite a few more..
            )
        ),
      
    );
  
    function convert($fn,$x,$y,$pg)
    {
        //a) copy the files to winedir
        //b) run the conversion
        //c) copy (link) out the files (and delete)
        
        
        
        $tn = $this->targetName($fn, $x,$y);
        if (file_exists($tn)) {
            return $tn;
        }
        
        $wine = $this->which('wine');
        $xvfb = $this->which('xvfb-run');
        $timeout = $this->which('timeout');
        
        $from = $this->tempName("dwg",true);
        $fromb = basename($from);
        $to = $this->tempName("dwf",true);
        $tob = basename($to);
        
        
        $dir = '/var/www/.wine/drive_c/';
        $wfrom = $dir . $fromb;
        $wto = $dir . $tob;
        $this->deleteOnExitAdd($wfrom);
        $this->deleteOnExitAdd($wto);
        copy($fn,$wfrom);
        
        
        // this is quite slow - so we probably only want to run it once
        $uinfo = posix_getpwuid(posix_getuid());
        $lock = session_save_path() . '/_wine_dwg2dwf_lock_' . $uinfo['name'] ;
        for ($i =0 ;$i< 5; $i++) {
            
            if (!file_exists($lock) ) {
                break;
                
            }
            if (fileatime($lock) < time() - 240 ) {
                @unlink($lock);
                break;
            }
            
            
            $this->debug("wine is locked - wating for it to clear");
            sleep(20);
            clearstatcache();
        }
         
        $this->deleteOnExitAdd($lock);

        touch($lock);
        
        // should really check if exe exists.
        chdir($uinfo['dir'] . '/.wine/drive_c');
        
        // /Recover = seems to handle hang situations
        $cmd = "{$timeout} 60s {$xvfb} --auto {$wine} \"" . $uinfo['dir'] . "/.wine/drive_c/Program Files (x86)//AutoDWG/DWG to DWF 2019/G2F.exe\" " .
                
                " /O C:\\\\{$tob} " . // output
                
                " /F C:\\\\{$fromb} " ;
             
            
        $this->exec($cmd);
        
        @unlink($lock);
        @unlink($wfrom);
        
        
        if (!file_Exists($wto)) {
            // failed.
            return false;
        }
        
        copy( $wto,$tn);
        @unlink($wto);
        
        clearstatcache();
        return file_exists($tn) ? $tn : false;
        
        
    }
    
    
}
