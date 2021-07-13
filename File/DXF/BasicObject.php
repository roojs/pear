<?php

class File_DXF_BasicObject
{

    function __construct($cfg=array())
    {
        foreach($cfg as $k=>$v) {
          	$this->$k = $v;
	    }
    }
    
    // Skip parsing a section
    function skipParseSection($dxf) {
        while ($pair = $dxf->readPair()){
            if ($pair['key'] == 0 && $pair['value'] == 'ENDSEC') {
                // End of a section
                return;
            }
        }
    }

    // Skip parsing a variable
    function skipParseVariable($dxf) {
        while($pair = $dxf->readPair()) {
            if ($pair['key'] == 0 || $pair['key'] == 9) {
                // End of a variable
                $dxf->pushPair($pair);
                return;
            }
        }
    }

    // Skip parsing a table
    function skipParseTable($dxf) {
        while($pair = $dxf->readPair()) {
            if ($pair['key'] == 0 && $pair['value'] == 'ENDTAB') {
                // End of a table
                return;
            }
        }
    }

    // Skip parsing a table entry
    function skipParseTableEntry ($dxf) {
        while($pair = $dxf->readPair()) {
            if ($pair['key'] == 0) {
                // End of a table entry
                $dxf->pushPair($pair);
                return;
            }
        }
    }

    function skipParseEntity ($dxf) {
        // same behavior as skipParseTableEntry($dxf)
        $this->skipParseTableEntry($dxf);
    }

    function skipParseApplicationDefinedGroup ($dxf) {
        while($pair = $dxf->readPair()) {
			if ($pair['key'] == 102 && $pair['value'] == "}") {
				// End of an application defined group
                $dxf->pushPair($pair);
				return;
			}
		}
    }

    function skipParseExtendedData ($dxf) {
        // same behavior as skipParseTableEntry($dxf)
        $this->skipParseTableEntry($dxf);
    }

    /*
     * OLD CODE BELOW
     */

    // public static $id = 1;
    // protected $handle;

    /**
     * BasicObject constructor.
     */
    /*
    function __construct() {
        $this->handle = self::$id++;
    }
    */

    /**
     * Returns a hexadecimal representation of the objects id.
     *
     * @return string
     */
    /*
    public function getHandle() {
        return $this->idToHex($this->handle);
    }
    

    public function getName() {
        return isset($this->name) ? $this->name : $this->getHandle();
    }
    */

    /**
     * @param $id
     * @return string
     */
    /*
    public function idToHex($id) {
        return strtoupper(dechex($id));
    }
    */

    /**
     * @return int
     */
    /*
    public function getUniqueID() {
        return self::$id++;
    }
    */

    /**
     *
     */
    /*
    public function render() {

    }
    */

    /**
     * Function to convert an array with coordinates of a point
     * to a string.
     *
     * @param $point
     * @param int $offset
     * @return string
     */
    /*
    protected function point($point, $offset = 0) {
        $output = array();
        $groupCode = 10 + $offset;
        foreach ($point as $value) {
        array_push($output, $groupCode, sprintf("%.3f", $value));
        $groupCode += 10;
        }
        return implode(PHP_EOL, $output);
    }
    */
}
