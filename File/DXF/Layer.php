<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_Layer extends File_DXF_BasicObject
{
    
    public $name;
    public $flag;
    public $color;
    public $lineType;
    public $data = array();

    function parse($dxf) {

        while($pair = $dxf->readPair()) {
            if ($pair['key'] == 0) {
                // End of this table entry
                $dxf->pushPair($pair);
                return;
            }
            $this->data[$pair['key']] = $pair['value'];
        }
    }
    
    /**
    * Public function to render an entity, returns a string representation of
    * the entity.
    * @return string
    */
    function render() {
        $output = array();
        array_push($output, 0, "LAYER");
        array_push($output, 5, $this->getHandle());
        array_push($output, 100, "AcDbSymbolTableRecord");
        array_push($output, 100, "AcDbLayerTableRecord");
        array_push($output, 2, strtoupper($this->name));
        array_push($output, 70, $this->flag);
        array_push($output, 62, $this->color);
        array_push($output, 6, $this->lineType);
        return implode(PHP_EOL, $output);
    }
}
