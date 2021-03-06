<?php

/**
 *
 * An alpha interface to samba browsing with the libsmb client.
 *
 *
 */

require_once 'File/Smb/Dir.php';

class File_Smb  extends File_Smb_Dir  {
    
    
    const FILE = 1;
    const DIR = 2;
    const SHARE = 4;
    
    
    
    
    /**
     * static map of server => [ user , pass ]
     */
    static $auth = array();
    
    static $connection = array();
    
    /**
     * constructor
     * 
     * @param:  $con (string)  - connection string:   USER\\WORKGROUP%PASS@/SERVER/SHARE - we use unix paths here (converted to smb by lib.)
     *  
     * 
     */
    
    
    function __construct($con)
    {
        
        $lr = explode("@", $con);
        $bits = explode("/", $lr[1]);
        $this->server  = $bits[0];
        $this->path = $bits[1];
        $this->name = $bits[1];
        $this->type = self::DIR  + self::SHARE;
        
        
        $bb = explode('%', $lr[0]);
        $u = $bb[0];
        $ws = null;
        $pass = $bb[1];
        if (strpos('\\', $bb[0]) > -1) {
            list($u,$ws) = explode("\\", $bb[0]);
        }
        
        $auth = File_Smb::$auth[$this->server] = array($ws, $u, $pass);
        
        if (!isset(File_Smb::$connection[$this->server])) {
            $con = File_Smb::$connection[$this->server] = smbclient_state_new();
            //print_R(array('connect', $auth[0], $auth[1], $auth[2]));
            
            smbclient_state_init($con , $auth[0], $auth[1], $auth[2]);
        }
        
   
        $this->resource = File_Smb::$connection[$this->server];
        
        
    }
    
    function ctorDir($path)
    {
        // so path is a full path
        // DIR constructor
        
        if ($path == $this->path) {
            return $this;
        }
        
        $bits = explode('/',$path);
        
        $name = array_pop($bits);
        if (count($bits)) {
            $fake = clone($this);
            $fake->path = implode('/', $bits);
            return new File_Smb_Dir($fake, $name);
        }
        $fake = clone($this);
        $fake->path = $name;
        return $fake;
    }
    
    function unlink()
    {
     
        throw new File_Smb_Exception_RmdirFailed("You can not call unlink at the top level...", 0);
     
    }
    
}
