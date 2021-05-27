<?php
/**
 *  https://www.verydoc.com/dwg-to-image.html
 
 
 * xhost + (as me)
 * sudo su www-data
 * export DISPLAY=:0.0
 * wine dwg2img.exe

 
*/
class File_Convert_Solution_dwg2img extends File_Convert_Solution
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
                'image/jpeg'
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
        $to = $this->tempName("jpg",true);
        $tob = basename($to);
        
        
        $dir = '/var/www/.wine/drive_c/';
        $wfrom = $dir . $fromb;
        $wto = $dir . $tob;
        $this->deleteOnExitAdd($wfrom);
        $this->deleteOnExitAdd($wto);
        copy($fn,$wfrom);
        
        
        // this is quite slow - so we probably only want to run it once
        $uinfo = posix_getpwuid(posix_getuid());
        $lock = session_save_path() . '/_wine_dwg2jpg_lock_' . $uinfo['name'] ;
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
        $cmd = "{$timeout} 60s {$xvfb} --auto {$wine} \"" . $uinfo['dir'] . "/.wine/drive_c/verydoc_dwg2image_cmd/dwg2img.exe\" " .
                " -dpi 300 " . // could do --width --height
                //-fontdir <string>  : a folder contain .ctb, .shx, etc. files
                " -fontdir C:\\\\verydoc_dwg2image_cmd\fonts " .
        
                //-width <int>       : set width, unit is point
                //-height <int>      : set height, unit is point
                //-linewidth <string>: set line width, unit in mm
                //-colormode <int>   : set color mode, 0 is color and 1 is black and white
                //-bgcolor <int>     : set background color, same as AutoCAD color index
                //-zoomtype <int>    : set zoom type, 1 is 'Zoom All' and 2 is 'Zoom Extend'
                " -zoomtype 2 " .
                //-$ <string>        : input registration key
                "C:\\\\{$fromb} C:\\\\{$tob}" .
             
            
        $this->exec($cmd);
        
        @unlink($lock);
        @ulink($wfrom);
        
        
        if (!file_Exists($wto)) {
            // failed.
            return false;
        }
        
        copy( $wto,$tn);
        @ulink($wto);
        
        clearstatcache();
        return file_exists($tn) ? $tn : false;
        
        
    }
    
    
}
