<?php
/**
 *
 * xhost + (as me)
 * sudo su www-data
 * export DISPLAY=:0.0
 * wine anydpp.exe

 * cd /var/www/.wine/Program\ Files\ \(x86\)/Any\ DWG\ to\ PDF\ Converter\ Pro
 * /usr/bin/xvfb-run --auto /usr/bin/wine dp.exe /InFile C:\\KNT1431-BO-TP-001.dwg /OutFile C:\\KNT1431-BO-TP-001.pdf /OutMode AlltoOne /Overwrite /OutLayout Paper /OutArea ZoomExtends
 
*/
class File_Convert_Solution_anydwgtopdf {
    
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
               'application/pdf'
            )
        ),
      
    );   
    function convert($fn,$x,$y,$pg)
    {
        //a) copy the files to winedir
        //b) run the conversion
        //c) copy (link) out the files (and delete)
        
        $wine = $this->which('wine');
        $xvfb = $this->which('xvfb-run');
        
        $from = $this->tempName("dwg");
        $fromb = basename($from);
        $to = $this->tempName("pdf");
        $tob = basename($to);
        
        $dir = '/var/www/.wine/drive_c/';
        link($dir . basename($from));
        
        
        
        
        $cmd = "{$xvfb} --auto {$wine} /InFile C:\\{$fromb} /OutFile C:\\{$tob}" .
            "/OutMode AlltoOne /Overwrite /OutLayout Paper /OutArea ZoomExtends"
        $this->exec($cmd);
        
    }
    
    
}
