<?php
require_once 'File/Convert/Solution.php';

/**
 * Fit-and-pad image scaling.
 *
 * Size spec: {a}q{b}
 *  - Scale the source so it fits inside a square of side min(a,b),
 *    preserving the aspect ratio. If the image already fits inside that
 *    square, it is not scaled up (only downscaling when needed).
 *  - Pad the result, centered, onto an {a}x{b} canvas.
 *
 * Both dimensions are required. If either is empty/zero this returns false.
 */
class File_Convert_Solution_scaleimageq extends File_Convert_Solution
{
    static $rules = array(
    );

    function targetName($fn, $x, $y)
    {
        return $fn . '.' . $x . 'q' . $y . '.' . $this->ext;
    }

    function convert($fn, $x, $y, $pg)
    {
        if (empty($x) || empty($y)) {
            return false;
        }
        $ext = $this->ext;
        $target = $fn . '.' . $x . 'q' . $y . '.' . $ext;
        if (file_exists($target) && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }

        $smaller = min((int) $x, (int) $y);
        // '>' = only resize when width or height exceeds the box; never upscale small images
        $scale = "{$smaller}x{$smaller}>";

        // background: white by default; transparent when option is set (matches scaleimage)
        $background = !empty(self::$options['transparent_background'])
            ? '-background none'
            : '-background white';

        $extent = " {$background} -gravity center -extent {$x}x{$y}";

        require_once 'System.php';
        $CONVERT = System::which("convert");
        $targetName = $target;
        $strip = '-strip';
        if ($this->to == 'image/x-ms-bmp') {
            $targetName = "bmp3:$target";
            $strip = '';
        }

        if ($CONVERT) {
            $cmd = "{$CONVERT} $strip -colorspace sRGB -interlace none -density 300 -quality 90 " .
                " -resize '{$scale}' " . $extent . " '{$fn}' '{$targetName}'";

            $cmdres = $this->exec($cmd);
            $this->exec($cmd);
        } else {
            die("not supported yet...");
        }

        clearstatcache();
        return file_exists($target) && filesize($target) ? $target : false;
    }
}
