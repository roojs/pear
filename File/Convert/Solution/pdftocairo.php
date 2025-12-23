<?php

class File_Convert_Solution_pdftocairo extends File_Convert_Solution
{
    
   
    static $rules = array(
        array(
         
            'from' =>    array( //source
                 'application/pdf',
               //     'application/tiff',
            ),
            'to' =>    array( //target
                    'image/jpeg',
                //    'image/gif',
                    'image/png',
            )
        ),
        
         
    );
      
    
      // new version - but does not appear to work that well..
    
    function convert($fn, $x, $y, $pg)
    {
        // Handle 'c' parameter (crop mode) - extract numeric width value
        if (is_string($x) && strpos($x, 'c') !== false) {
            // Extract width before 'c' (e.g., '200c289' -> '200')
            $x = preg_replace('/c.*$/', '', $x);
        }
        if (is_string($x) && strpos($x, 'x') !== false) {
            // Extract width before 'x' (e.g., '200x289' -> '200')
            $bits = explode('x', $x);
            $x = $bits[0];
        }
        $x = is_numeric($x) ? (int)$x : 0;
        
        $xscale = 600; // min size?
        if (!empty($x) && $x> $xscale ) {
            $xscale = $x;
        }
        
        
        $ext = $this->ext; //'png'; //$this->ext;
        
        $target = $fn . '-' . $xscale . '.' .  $ext;
        if ($pg !== false) {
            $target = $fn . '-' . $xscale . '-pg'. $pg . '.' .  $ext;
        }
        $finaltarget = $target ; //. ($this->ext == 'png' ?  '' : '.jpeg');
        
        $this->debug("final target check - $finaltarget ");
        $this->debug("FE: " . (file_exists($finaltarget)  ? 1 : 0));
        $this->debug("FS0: " . (file_exists($finaltarget) && filesize($finaltarget) ? 1  : 0));
        $this->debug("FS: " . (file_exists($finaltarget) ? (filemtime($finaltarget) . ">" . filemtime($fn)) : 'n/a'));
                     
        if ($this->debug < 2 && file_exists($finaltarget)  && filesize($finaltarget) && filemtime($finaltarget) > filemtime($fn)) {
            $this->debug("final target exists - $finaltarget - skipping");
            return $finaltarget;
        }
        require_once 'System.php';
        
        
        
        // revised version using pdfinfo and pdftoppm
        
        $PDFINFO = System::which("pdfinfo");
        $this->debug("PDFINFO: $PDFINFO");
        $GREP = System::which("grep");
        $this->debug("GREP: $GREP");
        $STRINGS= System::which("strings");
        $this->debug("PDFINFO: $STRINGS");
        // needs strings if starngs chars are in there..
        $cmd = $PDFINFO . ' '. escapeshellarg($fn) . " | $STRINGS | $GREP 'Page size'";
        
        
         
//        var_dump($cmd);exit;
        $info = trim( $this->exec($cmd));
        $match = array();
        // very presumtiuos...
        $cmd = $PDFINFO . ' '. escapeshellarg($fn) . " | $STRINGS | $GREP 'Page rot'";
        $rinfo = trim( $this->exec($cmd));
        
        if (!preg_match("/([0-9.]+)[^0-9]+([0-9.]+)/",$info, $match)) {
            $this->cmd .= " could not find 0-0 in the return string";
            return false;
        }
        $rot = 0;
        $rmatch = 0;
        if (preg_match("/([0-9.]+)/",$rinfo, $rmatch)) {
            $rot  = $rmatch[1];
            
        } 
        if (in_array((int)$rot, array(90,270))) {
             $match[0] = $match[1];
            $match[1] = $match[2];
            $match[2] = $match[0];
        }
        
        $yscale =  floor( ($match[2] / $match[1]) * $xscale) * 3;
        $xscale = floor($xscale) * 3;
        
        // Apply maximum dimension limits to prevent cairo errors
        // Cairo has limits around 32767 pixels, but we'll use 20000 as a safe maximum
        $maxDimension = 20000;
        
        if ($xscale > $maxDimension || $yscale > $maxDimension) {
            // Calculate scale factor to fit within max dimensions while maintaining aspect ratio
            $scaleFactor = min($maxDimension / $xscale, $maxDimension / $yscale);
            $xscale = floor($xscale * $scaleFactor);
            $yscale = floor($yscale * $scaleFactor);
            $this->debug("Scaled down dimensions to fit max limit: {$xscale}x{$yscale}");
        }
        
        $pg = ($pg === false) ? 1 : $pg;
        
        
        // older versions only support png...
        
//        print_r($xscale);
//        print_r($yscale);
        
        $PDFTOPPM = System::which("pdftocairo");
        if (!$PDFTOPPM) {
            $this->debug("NO PDFTOCAIRO trying pdftoppm");
            return $this->pdftoppm($fn,$x,$y, $pg);
            
        }
        // When only width is specified (y is empty), use only -scale-to-x to maintain aspect ratio
        // This prevents squashing of tall PDFs (like converted images)
        $scaleCmd = '';
        if (empty($y)) {
            // Only scale by width, let height scale proportionally
            $scaleCmd = " -scale-to-x {$xscale} ";
        } else {
            // Both dimensions specified, scale to fit both
            $scaleCmd = " -scale-to-x {$xscale} -scale-to-y {$yscale} ";
        }
        
        $cmd = "$PDFTOPPM   -f $pg " 
                    . "-l $pg  " 
                    //. "-png "
                    . "-r 300 " // was 1200?
//                    . "-rx 1200 "
//                    . "-ry 1200 "
                    . '-' . $ext . " "
                    . $scaleCmd
                    .  escapeshellarg($fn) . " " 
                    . escapeshellarg($fn.'-conv');
        
        // expect this file..
//        echo "$cmd <br/>";exit;
         
        $res = $this->exec($cmd);
        $this->result = $res;
        
        clearstatcache();
        // for some reason this makes 01 or 1?
        $out = $fn . sprintf('-conv-%d.'.str_replace('e', '', $ext) , $pg);
        
        $fe = file_exists($out)  && @filesize($out) ? true : false;  // no idea - we do a clearstatcache - and it still says cant stat filesize
        if ($fe) {
            $this->debug("GOT conv file: renaming $out to $target");
            @rename($out, $target);   // we do a clearstatcache, and file exists - but this still triggers errror
            
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
        clearstatcache();
   
        $fe = file_exists($out)  && filesize($out) ? true : false;
        if ($fe) {
            $this->debug("GOT conv file: renaming $out to $target");
            @rename($out, $target); // hide this as we still seem to get errors even after clearstat/file_exists..
            @chmod($target,fileperms($fn));
            return $target;
            
            print_R('in?');exit;
            $ret = $this->ext == 'png' ? $target: $this->convert($target);
            @chmod($ret,fileperms($fn));
            return $ret;
        }
        
        $out = $fn . sprintf('-conv-%03d.'.str_replace('e', '', $ext), $pg);
        //$out = $fn . '-conv-001.jpg'; .. if more than 100 pages...
        clearstatcache();

        $fe = file_exists($out)  && filesize($out) ? $out : false;
        if ($fe) {
            $this->debug("GOT conv file: renaming $out to $target");
             rename($out, $target);
            @chmod($target,fileperms($fn));
            return $target;
            
            print_R('in?');exit;
            
            //$ret = $this->ext == 'png' ? $target: $this->convert($target);
            @chmod($ret,fileperms($fn));
            return $ret;
        }
        
        $this->debug("Could not find OUTPUT FROM pdftocairo");
        
        
        return  false;
        
    }
    
    
    
    /**
     * This is the 'failback' version if pdfcario is not installed...
     *
     */
    
    function pdftoppm($fn, $x, $y, $pg=false)
    {
        // Handle 'c' parameter (crop mode) - extract numeric width value
        if (is_string($x) && strpos($x, 'c') !== false) {
            // Extract width before 'c' (e.g., '200c289' -> '200')
            $x = preg_replace('/c.*$/', '', $x);
        }
        if (is_string($x) && strpos($x, 'x') !== false) {
            // Extract width before 'x' (e.g., '200x289' -> '200')
            $bits = explode('x', $x);
            $x = $bits[0];
        }
        $x = is_numeric($x) ? (int)$x : 0;
        
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
        
        // Apply maximum dimension limits to prevent cairo errors
        // Cairo has limits around 32767 pixels, but we'll use 8000 as a safe maximum
        $maxDimension = 8000;
        
        if ($xscale > $maxDimension || $yscale > $maxDimension) {
            // Calculate scale factor to fit within max dimensions while maintaining aspect ratio
            $scaleFactor = min($maxDimension / $xscale, $maxDimension / $yscale);
            $xscale = floor($xscale * $scaleFactor);
            $yscale = floor($yscale * $scaleFactor);
            $this->debug("Scaled down dimensions to fit max limit: {$xscale}x{$yscale}");
        }
        
        $pg = ($pg === false) ? 1 : $pg;
        
        
        // older versions only support png...
        
//        print_r($xscale);
//        print_r($yscale);
        
        $PDFTOPPM = System::which("pdftoppm");
        if (!$PDFTOPPM) {
            echo "pdftoppm to available - install poppler-utils";
            return false;
            
        }
        // When only width is specified (y is empty), use only -scale-to-x to maintain aspect ratio
        // This prevents squashing of tall PDFs (like converted images)
        $scaleCmd = '';
        if (empty($y)) {
            // Only scale by width, let height scale proportionally
            $scaleCmd = " -scale-to-x {$xscale} ";
        } else {
            // Both dimensions specified, scale to fit both
            $scaleCmd = " -scale-to-x {$xscale} -scale-to-y {$yscale} ";
        }
        
        $cmd = "$PDFTOPPM -f $pg " 
                    . "-l $pg  " 
                    //. "-png "
                    . "-r 1200 "
//                    . "-rx 1200 "
//                    . "-ry 1200 "
                    . '-' . $ext . " "
                    . $scaleCmd
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
}