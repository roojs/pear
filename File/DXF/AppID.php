<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_AppID extends File_DXF_BasicObject
{
    
    public $name;
    public $data = array();

    function parse($dxf) {

        while($pair = $dxf->readPair()) {
            if ($pair['key'] == 0) {
                // End of this table entry
                $dxf->pushPair($pair);
                return;
            }
            if ($pair['key'] == 2) {
                $this->name = $pair['value'];
                continue;
            }
            $this->data[$pair['key']] = $pair['value'];
        }
    }
    
    /**
    * Public function to render an entity, returns a string representation of
    * the entity.
    * @return string
    */
    function render() 
    {
        $output = array();
        array_push($output, 0, "APPID");
        array_push($output, 5, $this->getHandle());
        array_push($output, 100, "AcDbSymbolTableRecord");
        array_push($output, 100, "AcDbRegAppTableRecord");
        array_push($output, 2, strtoupper($this->name));
        array_push($output, 70, $this->flag);
        return implode(PHP_EOL, $output);
    }
}
