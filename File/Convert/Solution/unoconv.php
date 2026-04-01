<?php

class File_Convert_Solution_unoconv extends File_Convert_Solution
{
    static $rules = array(
        array (
            'from' =>  array( // source
                //      'text/html', /// testing..
                'application/msword',
                'application/mswordapplication',
                'application/vnd.oasis.opendocument.text',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ),
            'to' =>  array( 
                'application/msword',
                'application/vnd.oasis.opendocument.text',
                'application/pdf',
                'text/html',
            )
        ),
        array(
            'from' => array( //source
                    
                    'application/vnd.ms-excel',
                    'application/vnd.oasis.opendocument.spreadsheet' ,
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    
                    
                ),
            'to' =>     array( //target
                    'application/vnd.ms-excel',
                    'application/vnd.oasis.opendocument.spreadsheet' ,
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/pdf',
                    'text/html',
                )
        ),
        array(
         
            'from' =>    array( //source
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ),
            'to' =>    array( //target
                    'application/pdf',
            )
        ),
        
    );
    
      
    
    //FIXME this method run 3 times??
    function convert($fn,$x,$y,$pg) 
    {
        $try = $x;
        $ext = $this->ext;
        
        
        $target =   $fn  . '.' . $ext;
        
        
        if ( file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            $this->debug("UNOCONV SKIP target exists");
            return $target;
        }
        $bits = explode('.', $fn);
        
        $from = $this->tempName(array_pop($bits),true);
        $to = $this->tempName($ext,true);
        
        copy($fn, $from);
        
        require_once 'System.php';
        
        $timeout = System::which('timeout');
        // fix the home directory - as we can't normally write to www-data's home directory.
        putenv('HOME='. ini_get('session.save_path'));
        $libreoffice = System::which('libreoffice');
        if (empty($libreoffice)) {
            $this->debug("missing libreoffice");
            $this->cmd = "Missing libreoffice";
            return false;
        }
        $output_dir = dirname($to);
        
        // Use LibreOffice headless conversion (no xvfb-run needed)
        $cmd = "$timeout 5m $libreoffice --headless --convert-to $ext --outdir " . 
                escapeshellarg($output_dir) . " " . escapeshellarg($from) . " 2>&1";
        ////  echo $cmd;
      
        $res = $this->exec($cmd);
        
        //fclose($lock);
        
        /// this is to prevent soffice staying alive if we timeout...
      //  `/usr/bin/killall -9 soffice.bin`;
        
        clearstatcache();
        
        // LibreOffice creates output file with same base name as input but with new extension
        $input_basename = pathinfo($from, PATHINFO_FILENAME);
        $libreoffice_output = $output_dir . '/' . $input_basename . '.' . $ext;
        
        // Check if LibreOffice created the output file
        if (file_exists($libreoffice_output)) {
            copy($libreoffice_output, $target);
            @unlink($libreoffice_output);
            @unlink($from);
            clearstatcache();
            return $target;
        }
        
        // If conversion failed, try again
        if (!file_exists($libreoffice_output) || (file_exists($libreoffice_output) && filesize($libreoffice_output) < 400)) {
            // try again!!!!
            @unlink($libreoffice_output);
            clearstatcache();
            sleep(3);
            
            $res = $this->exec($cmd);
            clearstatcache();
        }
        
        @unlink($from);
        if (!file_exists($libreoffice_output)) {    
            return false;
        }
        
        // Copy the LibreOffice output to the target location
        copy($libreoffice_output, $target);
        @unlink($libreoffice_output);
        if ($ext == 'html') {
            $doc = new DOMDocument();
            $doc->loadHTMLFile($target, LIBXML_NOERROR + LIBXML_NOWARNING);
            $imgs = $doc->getElementsByTagName('img');
            foreach($imgs as $im) {
                $path = $im->getAttribute('src');
                if (file_exists(dirname($target).'/'. $path)) {
                    $ifn = dirname($target).'/'. $path;
                    $type = image_type_to_mime_type(exif_imagetype($ifn));
                    $im->setAttribute('src', 'data:'.$type.';base64,' . base64_encode(file_get_contents($ifn)));
                }
                
            }
            
            $doc->saveHTMLFile($target);
        }
        return $target;
     
    }
}