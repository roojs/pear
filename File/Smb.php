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
     * @param:  $con (string)  - connection string:   USER\WORKGROUP@PASS/SERVER/SHARE - we use unix paths here (converted to smb by lib.)
     *  
     * 
     */
    
    
    function __construct($con)
    {
        $bits = explode("/", $con);
        
        $this->server  = $bits[1];
        $this->path = $bits[2];
        $this->name = $bits[2];
        $this->type = self::DIR  + self::SHARE;
        
        if (strlen($bits[0])) {
            $bb = explode('@', $bits[0]);
            $auth = File_Smb::$auth[$this->server] = $bits[0];
            
            if (!isset(File_Smb::$connection[$this->server])) {
                $con = File_Smb::$connection[$this->server] = smbclient_state_new();
                smbclient_state_init($con , null, $auth[0], $auth[1]);
            }
            
        }
        $this->resource = File_Smb::$connection[$this->server];
        
        
    }
    
    
    
}
