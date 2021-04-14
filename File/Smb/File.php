<?php

require_once 'File/Smb.php';

class File_Smb_File extends File_Smb {
    
    
    var $server;
    var $resource; // smblicent resource
    var $path; // full path excluding server, including share.
    var $type;
    

    /**
     * constructor
     * 
     * @param:  $con (string)  - connection string:   USER\WORKGROUP@PASS/SERVER/SHARE - we use unix paths here (converted to smb by lib.)
     *  
     * 
     */
    
    
    function __construct($dir, $sub)
    {
        

        $this->server  = $dir->server;
        $this->path = $dir->path . '/' . $sub;
        $this->type = self::DIR;
        $this->resource = File_Smb::$connection[$this->server];
        $this->stat();

        
    }
    
    function download($target)
    {
        $fh =  smbclient_open( $this->resource, 'smb://' . $this->server . '/'. $this->path, 'r');
        if ($fh === false) {
            throw new Exception("SMB download : {$this->path} open Failed");
        }
        $fw = fopen($target, 'w');
        if (!$fw) {
            throw new Exception("SMB download: {$this->path} open write target Failed");
        }
        while (true) {
            $str = smbclient_read($this->resource, $fh, 4096);
            if (strlen($str) ==0 ) {
                break;
            }
            fputs($fw,$str);
        }
        fclose($fw);
        smbclient_close($this->resource, $fh);
        
    }
    
}