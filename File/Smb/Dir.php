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
    
    
    var $created_dt;
    var $updated_dt;
    var $accessed_dt;
    
    
    /**
     * constructor
     * 
     * @param:  $dir File_Smb_Dir
     * @param: string $sub - directory;
     *  
     * 
     */
    
    
    function __construct($dir, $sub)
    {
        
        
        $this->server  = $dir->server;
        $this->path = $dir->path . '/' . $sub;
        $this->name = $sub;
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
            $this->created_dt = date("Y-m-d H:i:s", $this->ctime);
        }
        if (isset($this->mtime)) {
            $this->updated_dt = date("Y-m-d H:i:s", $this->mtime);
        }
        if (isset($this->atime)) {
            $this->accessed_dt = date("Y-m-d H:i:s", $this->atime);
        }
        

    }
    
    /**
     * return the list of files in a folder.
     */
    
    function dir()
    {
        require_once 'File/Smb/File.php'; 

        // fixme - path compoenent should be uuencoded..
        $dh = smbclient_opendir($this->resource, 'smb://' . $this->server . '/'. $this->path);
        
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
}