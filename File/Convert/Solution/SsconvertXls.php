<?php

class File_Convert_Solution_SsconvertXls extends File_Convert_Solution {
     
    
    function convert($fn) 
    {
        
        
        
        
        $ext = $this->ext;
        $target = $fn . '.' . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'System.php';
        $ssconvert = System::which('ssconvert');
         if (!$ssconvert) {
            // eak.
            die("ssconvert is not installed");
        }
        
        $format = 'UNKNOWN'; ///?? error condition.
        
        switch($this->from) {
            
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                $from = 'Gnumeric_Excel:xlsx';
                break;
            
            case 'application/vnd.ms-excel':
                $from = 'Gnumeric_Excel:excel';
                break;
            
            default:
                 die("ssconvert used on unknown format:" . $this->from);
            
        }
        
        switch($this->to) {
            
            case 'application/vnd.ms-excel':
                $format = 'Gnumeric_Excel:excel_biff8';
                break;
            
            case 'text/csv':
                $format = 'Gnumeric_stf:stf_csv';
                break;
            
            case 'text/xml':
                $format = 'Gnumeric_XmlIO:sax';
                break;
            
            default:
                 die("ssconvert used on unknown format:" . $this->to);
        }
        
        $ssconvert_extra = '';
        $sheet = false;
        if (isset(File_Convert::$options['sheet'])) {
            $sheet = File_Convert::$options['sheet'];
            $ssconvert_extra = ' -S ';
        }
        
        $xvfb = System::which('xvfb-run');
        if (empty($xvfb) || !file_exists($xvfb)) {
              $cmd = "$ssconvert $ssconvert_extra  -I $from -T $format " .
                escapeshellarg($fn) . " " .
                escapeshellarg($target);
        } else {
             $cmd = "$xvfb -a $ssconvert $ssconvert_extra  -I $from -T $format " .
                escapeshellarg($fn) . " " .
                escapeshellarg($target);
        }
        
       
        ///echo $cmd;
        $this->exec($cmd);
        
        clearstatcache();
        
        if ($sheet !== false) {
            $b = basename($fn);
            $d = dirname($fn);
            
            if (file_exists($d)) {
                
                $list = glob($fn . '.' . $ext . '.*');
                foreach($list as $l){
                    $ll = $l;
                    $s = array_pop(explode('.', $ll));
                    if(in_array($s, $sheet)){
                        continue;
                    }
                    
                    unlink($l);
                    
                }
            }
            
            $target = $fn;
        }
        
        return  file_exists($target)  && filesize($target) ? $target : false;
     
    }