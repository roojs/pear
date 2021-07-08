<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_Table extends File_DXF_BasicObject
{
    public $name;
    public $entries = array();
    public $entryNames = array();
    
    function addEntry($entry)
    {
        $name = strtoupper($entry->name);
        if (!in_array($name, $this->entryNames)) {
            $this->entryNames[] = $name;
            $this->entries[] = $entry;
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
        array_push($output, 0, "TABLE");
        array_push($output, 2, strtoupper($this->name));
        array_push($output, 5, $this->getHandle());
        array_push($output, 330, 0);
        array_push($output, 100, "AcDbSymbolTable");
        array_push($output, 70, count($this->entries));
        foreach ($this->entries as $entry) {
            $output[] = $entry->render();
        }
        array_push($output, 0, "ENDTAB");
        return implode(PHP_EOL, $output);
    }
}
