<?php

require_once 'File/DXF/BaiscObject.php';

class File_DXF_LType extends File_DXF_BasicObject
{
    public $name;
    public $flag;
    public $description;
    public $lineType;
    public $lineElements;

    /**
     * @param $lineElement
     */
    function addLineElement($lineElement)
    {
        $this->lineElements[] = $lineElement;
    }

    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    function render()
    {
        $absolutLenght = array_map('abs', $this->lineElements);

        $output = array();
        array_push($output, 0, "LTYPE");
        array_push($output, 5, $this->getHandle());
        array_push($output, 100, "AcDbSymbolTableRecord");
        array_push($output, 100, "AcDbLinetypeTableRecord");
        array_push($output, 2, strtoupper($this->name));
        array_push($output, 70, $this->flag);
        array_push($output, 3, $this->description);
        array_push($output, 72, 65);
        array_push($output, 73, count($this->lineElements));
        array_push($output, 40, array_sum($absolutLenght));
        //TODO add working lineElements
        return implode(PHP_EOL, $output);
    }
}
