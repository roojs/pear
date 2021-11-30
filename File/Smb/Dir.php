<?php

 

class File_Smb_Dir {
    
    
    var $server;
    var $resource; // smblicent resource
    var $path; // full path excluding server, including share.
    var $type;
    var $name;
    var $namehash; // hash of name
    
    var $perm_denied  = false;
    var $ino;	//inode number ****
    var $mode;	//inode protection mode
    var $nlink;	//number of links
    var $uid;	//userid of owner *
    var $gid;	//groupid of owner *
    var $rdev;	//device type, if inode device
    var $size;	//size in bytes
    var $atime;	//time of last access (Unix timestamp)
    var $mtime;	//time of last modification (Unix timestamp)
    var $ctime;	//time of last inode change (Unix timestamp)
    var $blksize;	//blocksize of filesystem IO **
    var $blocks;
    
    
    var $created_datetime;
    var $updated_datetime;
    var $access_datetime;
    
    
    /**
     * constructor
     * 
     * @param:  $dir File_Smb_Dir
     * @param: string $sub - directory;
     *  
     * 
     */
    
    
    function __construct($dir, $sub, $base = false)
    {
         
        $this->server  = $dir->server;
        $this->path = $dir->path . '/' . $sub;
        $this->name = $base === false ? $sub : $base; // for overriding ...
        $this->namehash = sha1($this->name);
        $this->type = File_Smb::DIR;
        $this->resource = File_Smb::$connection[$this->server];
        $this->stat();
     
        
    }
    
    function stat()
    {
        //if (!is_readable('smb://' . $this->server . '/'. $this->path
        set_error_handler(function($errno, $errstr, $errfile, $errline)  {
            if (preg_match('/Permission denied/', $errstr)) {
                $this->perm_denied = true;
                return;
            }

        });
                 
        $ar = smbclient_stat($this->resource, 'smb://' . $this->server . '/'. $this->path);
        
        restore_error_handler();
        if ($ar == false) {
            return;
        }

        foreach($ar as $k=>$v) {
            if (!is_numeric($k)) {
                $this->$k = $v;
            }
        }
        if (isset($this->ctime)) {
            $this->created_datetime = date("Y-m-d H:i:s", $this->ctime);
        }
        if (isset($this->mtime)) {
            $this->updated_datetime = date("Y-m-d H:i:s", $this->mtime);
        }
        if (isset($this->atime)) {
            $this->access_datetime = date("Y-m-d H:i:s", $this->atime);
        }
        return; // ignore xattr?
        $acl_str = @smbclient_getxattr($this->resource, 'smb://' . $this->server . '/'. $this->path, 'system.nt_sec_desc.*+');
        
        $acls = explode(',',$acl_str);
        $this->acls = array();
        foreach($acls as $a) {
            $aa = explode(":", $a);
            if (empty($aa[1])) {
                continue;
            }
            if ($aa[0] == 'ACL') {
                if (!in_array($aa[1], $this->acls)) {
                    $this->acls[] = $aa[1];
                }
                continue;
            } 
            $this->{strtolower($aa[0])} = $aa[1];
            
            
            
        }

    }
    
    /**
     * return the list of files in a folder.
     */
    
    function dir()
    {
        require_once 'File/Smb/File.php'; 
        set_error_handler(array($this, 'error_handler'));
        // fixme - path compoenent should be uuencoded..
        $dh = smbclient_opendir($this->resource, 'smb://' . $this->server . '/'. $this->path);
        restore_error_handler();

        if (!$dh) {
            throw new Exception("Directory failed to open");
            //return array();
        }
        
        $ret = array();
        
        while (($e = smbclient_readdir($this->resource,$dh)) !== false) {
           //  print_R($e);
            switch($e['type']) {
                
                case 'file':
                    $ret[] = new File_Smb_File($this, $e['name']);
                    break;
                
                case 'directory':
                    if ($e['name'] == '.' || $e['name'] == '..') {
                        break;
                    }
                    $ret[] = new File_Smb_Dir($this, $e['name']);
                    break;
                
                case 'workgroup':
                case 'server':
                case 'file share':
                case 'printer share':
                case 'communication share':
                case 'IPC share':
                case 'link':
                case 'unknown':
                    echo "Unknown share type?\n";
                    break;
            }
        }
        //print_R($ret);exit;
        
        return $ret;
            
        
        
    }
    
    function error_handler($errno, $errstr, $errfile, $errline) {
        require_once 'File/Smb/Exception.php';
        restore_error_handler();
        switch($errno) {
            case 2:
                throw new File_Smb_ExceptionNotExist($errstr, $errno);
            case 13:
                throw new File_Smb_ExceptionPermDenied($errstr, $errno);
              
        }
         
    }
    
    function upload($local, $name)
    {
        if (empty($name)) {
            throw new Exception("UPloaded file needs a name");
        }
        $fh =  smbclient_open( $this->resource, 'smb://' . $this->server . '/'. $this->path .'/' . $name, 'w');
        if ($fh === false) { 
            throw new Exception("SMB upload : {$this->path} open Failed");
        }
        $fw = fopen($local, 'r');
        if (!$fw) {
            throw new Exception("SMB upload: {$local} open write target Failed");
        }
        while (true) {
            $str = fgets($fw, 4096);
            if (strlen($str) == 0 ) {
                break;
            }
            smbclient_write($this->resource, $fh, $str);
            
        }
        fclose($fw);
        smbclient_close($this->resource, $fh);
        require_once 'File/Smb/File.php';
        return new File_Smb_File($this, $name);
        
    }
    
    function unlink()
    {
        if (!@smbclient_rmdir($this->resource, 'smb://' . $this->server . '/'. $this->path )) {
            require_once 'File/Smb/Exception.php';

            throw new File_Smb_Exception_RemoveDirFailed("Rmdir failed", 0);
        }
    }
    
    
    function mkdir($name)
    {
        if (!@smbclient_mkdir($this->resource, 'smb://' . $this->server . '/'. $this->path . '/' . $name)) {
            require_once 'File/Smb/Exception.php';
            throw new File_Smb_Exception_MakeDirFailed("mkdir failed", 0);
        }
        return new File_Smb_Dir($this, $name);
        
        
    }
    
    function renameChild($from, $to)
    {
        smbclient_rename(
            $this->resource,
            'smb://' . $this->server . '/'. $this->path . '/'. $from ,
            'smb://' . $this->server . '/'. $this->path . '/'. $to
        );
        
        
    }
    
    
}