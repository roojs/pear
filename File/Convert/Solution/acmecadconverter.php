<?php
/**
 * http://www.dwgtool.com/buyconvert.htm
 * demo puts big watermark over image..
 
 http://www.dwgtool.com/cmd.htm
 
 
 * xhost + (as me)
 * sudo su www-data
 * export DISPLAY=:0.0
 * wine AcmeCADConverter.exe.exe

 
*/
class File_Convert_Solution_acmecadconverter extends File_Convert_Solution
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
                'image/jpeg', // can do quite a few more..
                'image/svg+xml',
                'application/pdf'
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
        // code does not like 'jpeg'?
        $to = $this->tempName($this->ext == 'jpeg' ? 'jpg' : $this->ext,true);
        $tob = basename($to);
        
        
        $dir = '/var/www/.wine/drive_c/';
        $wfrom = $dir . $fromb;
        $wto = $dir . $tob;
        $this->deleteOnExitAdd($wfrom);
        $this->deleteOnExitAdd($wto);
        copy($fn,$wfrom);
        
        
        // this is quite slow - so we probably only want to run it once
        $uinfo = posix_getpwuid(posix_getuid());
        $lock = session_save_path() . '/_wine_acmecadconverter_lock_' . $uinfo['name'] ;
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
        
        $format = 2;
        if ($this->ext == 'svg') {
            $format = 101;
        }
        if ($this->ext == 'pdf') {
            $format = 104;
        }

        
        // /Recover = seems to handle hang situations
        $cmd = "{$timeout} 60s {$xvfb} --auto {$wine} \"" . $uinfo['dir'] . "/.wine/drive_c/Program Files (x86)/Acme CAD Converter/AcmeCADConverter.exe\" " .
                " /r " . //command line
                " /o C:\\\\{$tob} " . // output
                " /e " . //auto zoom extent
                " /ls " . //paper space if pos
                " /f {$format}" . //2 == jpeg
                " /b 0" . // /b integer Indicate background color index, [0-black, 1....
                
                " C:\\\\{$fromb} " ;
             
            
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
