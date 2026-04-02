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

    /**
     * Remove a directory tree (LibreOffice profile under tmp-lo-*).
     */
    private static function removeLibreOfficeHomeDir($dir)
    {
        $items = @scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                self::removeLibreOfficeHomeDir($path);
                continue;
            }
            @unlink($path);
        }
        @rmdir($dir);
    }

    /**
     * Inline local image files as data: URLs in saved HTML (no placeholders).
     * Runs only when option imageToDataUrl is true and target mime is text/html.
     *
     * @param string $target Absolute path to HTML file
     * @param string $output_dir Absolute path to output directory
     */
    private function embedHtmlImagesAsDataUrlsIfRequested($target, $output_dir)
    {
        if (empty(self::$options['imageToDataUrl']) || $this->to !== 'text/html' || !file_exists($target)) {
            return;
        }

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTMLFile($target, LIBXML_NOERROR | LIBXML_NOWARNING);
        $imgs = $doc->getElementsByTagName('img');
        $modified = false;

        foreach ($imgs as $im) {
            $src = $im->getAttribute('src');
            if ($src === '') {
                $this->debug("Skipping empty image: " . $src);
                continue;
            }
            if (preg_match('#^data:#i', $src) || preg_match('#^https?://#i', $src)) {
                $this->debug("Skipping image: " . $src);
                continue;
            }
            $decodedSrc = urldecode($src);
            $candidates = array(
                $output_dir . '/' . $decodedSrc,
                $output_dir . '/' . $src,
            );
            $ifn = false;
            foreach ($candidates as $c) {
                if (file_exists($c) && is_file($c)) {
                    $ifn = $c;
                    break;
                }
            }
            if (!$ifn) {
                $this->debug("No image file found: " . $src);
                continue;
            }
            $imageInfo = @getimagesize($ifn);
            if ($imageInfo === false) {
                $this->debug("Failed to get image info: " . $src);
                continue;
            }
            $mime = $imageInfo['mime'];
            $im->setAttribute('src', 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($ifn)));
            $this->debug("Converted image to data URL: " . $src);
            $modified = true;
        }

        if ($modified) {
            $doc->saveHTMLFile($target);
        }
    }

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
        
        $from = $this->tempName(pathinfo($fn, PATHINFO_EXTENSION),true);
        $to = $this->tempName($ext,true);
        
        copy($fn, $from);
        
        require_once 'System.php';
        
        $timeout = System::which('timeout');
        $libreoffice = System::which('libreoffice');
        if (empty($libreoffice)) {
            $this->debug("missing libreoffice");
            $this->cmd = "Missing libreoffice";
            return false;
        }

        $loHome = rtrim(ini_get('session.save_path'), '/\\') . '/tmp-lo-' . str_replace('.', '', uniqid('', true));
        if (!@mkdir($loHome, 0700, true)) {
            $this->debug("Could not create LibreOffice HOME: {$loHome}");
            @unlink($from);
            return false;
        }
        $previousHome = getenv('HOME');
        putenv('HOME=' . $loHome);
        $output_dir = dirname($to);
        
        // Use LibreOffice headless conversion (no xvfb-run needed)
        $cmd = "$timeout 5m $libreoffice --headless --convert-to $ext --outdir " . 
                escapeshellarg($output_dir) . " " . escapeshellarg($from) . " 2>&1";
        //  echo $cmd;
      
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

            putenv('HOME=' . ($previousHome !== false ? $previousHome : ''));
            self::removeLibreOfficeHomeDir($loHome);
            $this->embedHtmlImagesAsDataUrlsIfRequested($target, $output_dir);
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

        putenv('HOME=' . ($previousHome !== false ? $previousHome : ''));
        self::removeLibreOfficeHomeDir($loHome);
        
        @unlink($from);
        if (!file_exists($libreoffice_output)) {    
            return false;
        }
        
        // Copy the LibreOffice output to the target location
        copy($libreoffice_output, $target);
        @unlink($libreoffice_output);
        $this->embedHtmlImagesAsDataUrlsIfRequested($target, $output_dir);
        return $target;
     
    }
}