<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_AppID extends File_DXF_BasicObject
{

    function parse($dxf) {

        $this->skipParseTableEntry($dxf);
    }
    
    /*
     * OLD CODE BELOW
     */

    // protected $name;
    // protected $flag;

    /**
     * AppID constructor.
     * @param $name
     * @param int $flag
     */
    /*
    function __construct($name, $flag = 0) {
        $this->name = $name;
        $this->flag = $flag;
        parent::__construct();
    }

    public function getName() {
        return $this->name;
    }
    */

    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    /*
    public function render() {
        $output = array();
        array_push($output, 0, "APPID");
        array_push($output, 5, $this->getHandle());
        array_push($output, 100, "AcDbSymbolTableRecord");
        array_push($output, 100, "AcDbRegAppTableRecord");
        array_push($output, 2, strtoupper($this->name));
        array_push($output, 70, $this->flag);
        return implode(PHP_EOL, $output);
    }
    */
}
