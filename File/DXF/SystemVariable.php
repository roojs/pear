<?php

require_once 'File/DXF/BasicObject.php';

const POINTITEMLABEL = 'point';

class File_DXF_SystemVariable extends File_DXF_BasicObject
{
    public $name;
    public $values;
    

    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    function render() {
        $output = array();
        array_push($output, 9, "$" . strtoupper($this->name));
        if (isset($this->values[POINTITEMLABEL])) {
            array_push($output, $this->point($this->values[POINTITEMLABEL]));
            unset($this->values[POINTITEMLABEL]);
        }
        foreach ($this->values as $groupCode => $value) {
            array_push($output, $groupCode, $value);
        }
        return implode(PHP_EOL, $output);
    }

}
