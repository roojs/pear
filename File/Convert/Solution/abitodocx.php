
    function abitodocx($fn)
    {
        require_once 'File/MimeType.php';
        $fmt = new File_MimeType();
        $fext = $fmt->toExt($this->from);
        
        $ext = $this->ext;
        $target = str_replace('.', '_', $fn) . '.' . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'File/Convert/AbiToDocx.php';
        $conv = new File_Convert_AbiToDocx($fn);
        $conv->save($target); 
        
        return  file_exists($target)  && filesize($target) ? $target : false;
    }