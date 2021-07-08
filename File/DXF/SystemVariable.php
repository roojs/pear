<?php

require_once 'File/DXF/BasicObject.php';

const POINTITEMLABEL = 'point';

class File_DXF_SystemVariable extends File_DXF_BasicObject
{
    public $name;
    public $data;

    function parse($dxf) {

        while($pair = $dxf->readPair()) {
            if ($pair['key'] == 0 || $pair['key'] == 9) {
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
        array_push($output, 9, "$" . strtoupper($this->name));
        if (isset($this->data[POINTITEMLABEL])) {
            array_push($output, $this->point($this->data[POINTITEMLABEL]));
            unset($this->data[POINTITEMLABEL]);
        }
        foreach ($this->data as $groupCode => $value) {
            array_push($output, $groupCode, $value);
        }
        return implode(PHP_EOL, $output);
    }

}
