<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_Style extends File_DXF_BasicObject
{
    public $name;
    public $flag;
    public $height;
    public $width;
    public $lineType;

    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    function render()
    {
        $output = array();
        array_push($output, 0, "STYLE");
        array_push($output, 5, $this->getHandle());
        array_push($output, 100, "AcDbSymbolTableRecord");
        array_push($output, 100, "AcDbTextStyleTableRecord");
        array_push($output, 2, strtoupper($this->name));
        array_push($output, 70, $this->flag);
        array_push($output, 6, $this->lineType);
        return implode(PHP_EOL, $output);
    }
}
