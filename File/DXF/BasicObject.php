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

    /**
     * @return int
     */
    function getUniqueID() {
        return self::$id++;
    }

    /**
     *
     */
    function render() {

    }

    /**
     * Function to convert an array with coordinates of a point
     * to a string.
     *
     * @param $point
     * @param int $offset
     * @return string
     */
    function point($point, $offset = 0) {
        $output = array();
        $groupCode = 10 + $offset;
        foreach ($point as $value) {
            array_push($output, $groupCode, sprintf("%.3f", $value));
            $groupCode += 10;
        }
        return implode(PHP_EOL, $output);
    }
}
