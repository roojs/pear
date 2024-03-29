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
        $xvfb = System::which('xvfb-run');
        if (empty($xvfb)) {
            $this->debug("missing xvfb-run");
            $this->cmd = "Missing xvfb";
            return false;
        }
        $uno = System::which('unoconv');
        if (empty($uno)) {
            $this->debug("missing unoconv");
            $this->cmd = "Missing unoconv";
            return false;
        }
        // before we used stdout -- not sure why.
        //$cmd = "$xvfb -a  $uno -f $ext --stdout " . escapeshellarg($fn) . " 1> " . escapeshellarg($target);
        $cmd = "$timeout 5m $xvfb -a  $uno -f $ext -o " . escapeshellarg($to) . " " . escapeshellarg($from);
        ////  echo $cmd;
        
        /*
        // do some locking WHY? 
        $lock = fopen(ini_get('session.save_path') . '/file-convert-unoconv.lock', 'wr+');
        $tries = 3;
        while ($tries >0) {
            if (!flock($lock, LOCK_EX | LOCK_NB)) {
                sleep(10);
                $tries--;
                continue;
            }
            $tries = -10;
            break; // got a lock.
        
        }
        if ($tries != -10) {
            die("could not get a lock to run unoconv - " . ini_get('session.save_path') . '/file-convert-unoconv.lock');
        }
        */
        $res = $this->exec($cmd);
        
        //fclose($lock);
        
        /// this is to prevent soffice staying alive if we timeout...
        `/usr/bin/killall -9 soffice.bin`;
        
        clearstatcache();
        
        
        
//        print_R($target);
//        print_r("--------\n");
//        var_dump(file_exists($target));
//        var_dump(is_dir($target));
       
        
        if (is_dir($to)) {
            // it's an old version of unoconv.
            $tmp = '/tmp/temp_pdf';
            if(!is_dir($tmp)){
                mkdir($tmp);
            }
            
            
            $dir = scandir($to, 1);
            
//            print_r($dir);
            
            $filename = $dir[0];
            $file = $to.'/'.$filename;
            
            copy($file, $target);
            
            
            unlink($to.'/'.$filename);
            rmdir($to);
            
            @unlink($from);
            
//            exit;
//            create temporary directory 
//            use scandir($target)[0]; to find first file
//            move it to the temporary directory
//            delete the target
//            move the new file to the target
            
            clearstatcache();
            return $target;
        }
        
//         exit;
        if (!file_exists($to) || (file_exists($to)  && filesize($to) < 400)) {
            //$this->cmd .= "\n" . filesize($target) . "\n" . file_get_contents($target);
            
            // try again!!!!
            @unlink($to);
            clearstatcache();
            sleep(3);
            
            $res = $this->exec($cmd);
            clearstatcache();
        
            
        }
        @unlink($from);
        if (!file_exists($to)) {    
            return false;
        }
        if ($ext == 'html') {
            $doc = new DOMDocument();
            $doc->loadHTMLFile($to,LIBXML_NOERROR + LIBXML_NOWARNING   );
            $imgs = $doc->getElementsByTagName('img');
            foreach($imgs as $im) {
                $path = $im->getAttribute('src');
                if (file_exists(dirname($to).'/'. $path)) {
                    $ifn = dirname($to).'/'. $path;
                    $type = image_type_to_mime_type(exif_imagetype($ifn));
                    $im->setAttribute('src', 'data:'.$type.';base64,' . base64_encode(file_get_contents($ifn)));
                }
                
            }
            
            $doc->saveHTMLFile($target);
            
        } else {
        
            copy($to, $target);
        }
        return $target;
     
    }
}