 <?php


class File_Convert_Solution_pdf2ppm extends File_Convert_Solution
{
    
   
    var $rules = array(
        array(
         
            'from' =>    array( //source
            
            ),
            'to' =>    array( //target
                
            )
        ),
        
         
    );
     
    /**
     * This is the 'failback' version if pdfcario is not installed...
     *
     */
    
    function convert($fn, $x, $y, $pg=false)
    {
        $xscale = 400; // min size?
        if (!empty($x) && $x> $xscale ) {
            $xscale = $x;
        }
        
        
        $ext = 'png'; /// older versions only support PNG.  $this->ext; //'png'; //$this->ext;
        
        $target = $fn . '-' . $xscale . '.' .  $ext;
        if ($pg !== false) {
            $target = $fn . '-' . $xscale . '-pg'. $pg . '.' .  $ext;
        }
        $finaltarget = $target ; //. ($this->ext == 'png' ?  '' : '.jpeg');
        
        
        if ($this->debug < 2 && file_exists($finaltarget)  && filesize($finaltarget) && filemtime($finaltarget) > filemtime($fn)) {
            
            $this->debug("using existing image - $finaltarget");
        
            return $finaltarget;
        }
        require_once 'System.php';
        
        
        // revised version using pdfinfo and pdftoppm
        
        $PDFINFO = System::which("pdfinfo");
        $GREP = System::which("grep");
        $STRINGS= System::which("strings");
        // needs strings if starngs chars are in there..
        $cmd = $PDFINFO . ' '. escapeshellarg($fn) . " | $STRINGS | $GREP 'Page size'";
         
        
         $info = trim( $this->exec($cmd));
        $match = array();
        // very presumtiuos...
       
       //print_R($info);
        if (!preg_match("/([0-9.]+)[^0-9]+([0-9.]+)/",$info, $match)) {
            $this->cmd .= " could not find 0-0 in the return string";
            return false;
        }
        
        $yscale =  floor( ($match[2] / $match[1]) * $xscale) * 3;
        $xscale = floor($xscale) * 3;
        $pg = ($pg === false) ? 1 : $pg;
        
        
        // older versions only support png...
        
//        print_r($xscale);
//        print_r($yscale);
        
        $PDFTOPPM = System::which("pdftoppm");
        if (!$PDFTOPPM) {
            echo "pdftoppm to available - install poppler-utils";
            return false;
            
        }
        $cmd = "$PDFTOPPM -f $pg " 
                    . "-l $pg  " 
                    //. "-png "
                    . "-r 1200 "
//                    . "-rx 1200 "
//                    . "-ry 1200 "
                    . '-' . $ext . " "
                    . " -scale-to-x {$xscale} " 
                    . " -scale-to-y {$yscale} " 
                    .  escapeshellarg($fn) . " " 
                    . escapeshellarg($fn.'-conv');
        
        // expect this file..
//        echo "$cmd <br/>";exit;
        $this->debug(  $cmd); 
        
        $res = $this->exec($cmd);
        $this->result = $res;
        
        clearstatcache();
        // for some reason this makes 01 or 1?
        $out = $fn . sprintf('-conv-%d.'.str_replace('e', '', $ext) , $pg);
        
        $fe = file_exists($out)  && filesize($out) ? $out : false;
        if ($fe) {
            rename($out, $target);
            
            @chmod($target,fileperms($fn));
            
            return $target;
            
            
            print_R('in?');exit;
            //FIXME never fun this???
            $ret = $this->ext == 'png' ? $target: $this->convert($target);
            @chmod($ret,fileperms($fn));
            
            return $ret;
        }
        $out = $fn . sprintf('-conv-%02d.'.str_replace('e', '', $ext), $pg);
        //$out = $fn . '-conv-01.jpg';
        
        $fe = file_exists($out)  && filesize($out) ? $out : false;
        if ($fe) {
             rename($out, $target);
            @chmod($target,fileperms($fn));
            return $target;
            
            print_R('in?');exit;
            $ret = $this->ext == 'png' ? $target: $this->convert($target);
            @chmod($ret,fileperms($fn));
            return $ret;
        }
        
        $out = $fn . sprintf('-conv-%03d.'.str_replace('e', '', $ext), $pg);
        //$out = $fn . '-conv-001.jpg'; .. if more than 100 pages...
        
        $fe = file_exists($out)  && filesize($out) ? $out : false;
        if ($fe) {
             rename($out, $target);
            @chmod($target,fileperms($fn));
            return $target;
            
            print_R('in?');exit;
            
            //$ret = $this->ext == 'png' ? $target: $this->convert($target);
            @chmod($ret,fileperms($fn));
            return $ret;
        }
        
        
        
        
        return  false;
        
    }