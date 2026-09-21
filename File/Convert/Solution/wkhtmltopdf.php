<?php

class File_Convert_Solution_wkhtmltopdf extends File_Convert_Solution
{
   
  

   
    static $rules = array(
        array(
         
            'from' =>    array( //source
                'text/html',
            ),
            'to' =>    array( //target
                 'application/pdf',
                 'image/png',
            )
        ),
    );
    



    function convert($fn, $x, $y, $pg)
    {
        // need a remove version for this..

        if (!empty(File_Convert::$options['webkit.url'])) {
            $target = $fn . '.png';
            if (file_exists($target) && filesize($target)) {
                if (is_file($fn) && filemtime($target) > filemtime($fn)) {
                    return $target;
                }
            }
            return $this->convertWebkit($fn, $target);
        }

        if ($this->to == 'image/png') {
            $this->debug('webkit.url required for text/html to image/png');
            return false;
        }

        $target = $fn . '.pdf';

        // should check dates...!!!!
        if (file_exists($target) && filesize($target)) {
            if (is_file($fn) && filemtime($target) > filemtime($fn)) {
                return $target;
            }
        }

        return $this->convertWkhtmltopdf($fn, $target);
    }

    function convertWkhtmltopdf($fn, $target)
    {
        require_once 'System.php';

        $conv = System::which('wkhtmltopdf');

        if (!empty(File_Convert::$options['wkhtmltopdf.bin'])) {
            $conv = System::which(File_Convert::$options['wkhtmltopdf.bin']);
            if (!$conv) {
                die("could not find ". File_Convert::$options['wkhtmltopdf.bin']);
            }
        }

        if (!empty(File_Convert::$options['wkhtmltopdf'])) {
            $conv .= File_Convert::$options['wkhtmltopdf'];
        }

        $cmd = $conv .' -n ' . escapeshellarg($fn) . ' ' .escapeshellarg($target);

        $res = $this->exec($cmd);
        clearstatcache();

        if (!file_exists($target) ) {
            // try with X wrapper..Xvfb

            $xvfb = System::which('xvfb-run');
            if (empty($xvfb) || !file_exists($xvfb)) {
                return false;
            }
            $cmd = $xvfb .' ' . $cmd;

            $res = $this->exec($cmd);
        }

        //echo $res;
        clearstatcache();
        return  file_exists($target)  && filesize($target) ? $target : false;
    }

    function convertWebkit($fn, $target)
    {
        require_once 'System.php';

        $xvfb = System::which('xvfb-run');
        $conv = System::which('webkitpdf2');
        if (empty($xvfb) || empty($conv)) {
            $this->debug('webkit missing xvfb-run or webkitpdf2');
            return false;
        }

        $width = !empty(File_Convert::$options['webkit.width'])
            ? (int) File_Convert::$options['webkit.width']
            : 1200;

        $screenW = (int) $width + 50;
        $cmd = $xvfb
            . " --auto-servernum --server-args='-screen 0 {$screenW}x2000x24+32' "
            . escapeshellarg($conv)
            . ' --url ' . escapeshellarg(File_Convert::$options['webkit.url'])
            . ' --width ' . (int) $width
            . ' --delay 3'
            . ' --png ' . escapeshellarg($target);

        $this->exec('timeout 90s ' . $cmd . ' 2>&1');
        clearstatcache();
        return file_exists($target) && filesize($target) ? $target : false;
    }
}
