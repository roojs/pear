<?php


require_once 'DB/DataObject/Generator.php';


class HTML_FlexyFramework_Generator extends DB_DataObject_Generator 
{
    // block class generation.
    function generateClasses()
    {
        return;
    }
    
    
    function generateReaders()
    {
        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        
        $out = array();
        foreach($this->tables as $this->table) {
            $this->table        = trim($this->table);
            
            $out = array_merge($out, $this->_generateReader($table));
            
            
        }
        print_r($out);
        exit;
        $tmpname = tempnam(session_save_path(),'reader');
        file_put_contents($tmpname, serialize($out));
        
        $perms = 0755;
        $target = $options["ini_{$this->_database}"] . '.reader.serial';
            
        // windows can fail doing this. - not a perfect solution but otherwise it's getting really kludgy..
        if (!@rename($tmpname, $target)) {
            unlink($target); 
            rename($tmpname, $target);
        }
            
        chmod($target, $perms);
    }
    function _generateReader($table)
    {
        echo '<PRE>'; print_r( $this->_definitions[$table] ); exit;
    }
}
