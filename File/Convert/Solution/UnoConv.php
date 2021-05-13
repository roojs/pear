<?php

class File_Convert_Solution_UnoConv extends File_Convert_Solution
{
    var $rules = array(
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
                    
                ),
            'to' =>     array( //target
                    'application/vnd.ms-excel',
                    'application/vnd.oasis.opendocument.spreadsheet' ,
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/pdf',
                    'text/html',
                )
        )
        
        
    );
    
     
    
            
    
    
    //FIXME this method run 3 times??
    function convert($fn, $try=0) 
    {
        
        $ext = $this->ext;
        
        
        $target = str_replace('.', '_', $fn) . '.' . $ext;
        
        
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        
        
        
        
        require_once 'System.php';
        
        $timeout = System::which('timeout');
        // fix the home directory - as we can't normally write to www-data's home directory.
          
        putenv('HOME='. ini_get('session.save_path'));
        $xvfb = System::which('xvfb-run');
        if (empty($xvfb)) {
            $this->cmd = "Missing xvfb";
            return false;
        }
        $uno = System::which('unoconv');
        if (empty($uno)) {
            $this->cmd = "Missing unoconv";
            return false;
        }
        // before we used stdout -- not sure why.
        //$cmd = "$xvfb -a  $uno -f $ext --stdout " . escapeshellarg($fn) . " 1> " . escapeshellarg($target);
        $cmd = "$timeout 30s $xvfb -a  $uno -f $ext -o " . escapeshellarg($target) . " " . escapeshellarg($fn);
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
       
        
        if (is_dir($target)) {
            // it's an old version of unoconv.
            $tmp = '/tmp/temp_pdf';
            if(!is_dir($tmp)){
                mkdir($tmp);
            }
            
            
            $dir = scandir($target, 1);
            
//            print_r($dir);
            
            $filename = $dir[0];
            $file = $target.'/'.$filename;
            
            copy($file, $tmp.'/'.$filename);
            
            
            unlink($target.'/'.$filename);
            rmdir($target);
            
            copy($tmp.'/'.$filename, $target);
            
//            exit;
//            create temporary directory 
//            use scandir($target)[0]; to find first file
//            move it to the temporary directory
//            delete the target
//            move the new file to the target
            
            clearstatcache();
        }
        
//         exit;
        if (!file_exists($target) || (file_exists($target)  && filesize($target) < 400)) {
            //$this->cmd .= "\n" . filesize($target) . "\n" . file_get_contents($target);
            
            // try again!!!!
            @unlink($target);
            clearstatcache();
            sleep(3);
            
            $res = $this->exec($cmd);
            clearstatcache();
        
            
        }
        
//        print_r($target);
        return  file_exists($target) ? $target : false;
     
    }
}