<?php

class File_DXF_BasicObject
{
    public static $id = 1;
    public $handle;

    function __construct($cfg=array())
    {
        // unique id for each instance
        $this->handle = self::$id++;
        foreach($cfg as $k=>$v) {
          	$this->$k = $v;
	    }
    }
    
    /**
    * Returns a hexadecimal representation of the objects id.
    *
    * @return string
    */
    function getHandle() {
        return $this->idToHex($this->handle);
    }

    /**
    * @param $id
    * @return string
    */
    function idToHex($id) {
        return strtoupper(dechex($id));
    }
    
}
