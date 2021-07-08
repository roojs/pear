<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_Table extends File_DXF_BasicObject
{
    public $name;
    public $data;
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

    function parse($dxf)
    {
        while ($pair = $dxf->readPair()) {
            if ($pair['key'] == 0 && $pair['value'] == "ENDTAB") {
                // End of a table
                return;
            }
            if ($pair['key'] == 2) {
                switch ($this->name) {
                    case 'LTYPE':
                        $this->addEntry($dxf->factory('LType', array('name' => $pair['value'])));
                        break;
                    case 'STYLE':
                        $this->addEntry($dxf->factory('Style', array('name' => $pair['value'])));
                        break;
                    case 'LAYER':
                        $this->addEntry($dxf->factory('Layer', array('name' => $pair['value'])));
                        break;
                    case 'APPID':
                        $this->addEntry($dxf->factory('AppID', array('name' => $pair['value'])));
                        break;
                    case 'BLOCK_RECORD':
                        $this->addEntry($dxf->factory('BlockRecord', array('name' => $pair['value'])));
                        break;
                    case 'DIMSTYLE':
                        $this->addEntry($dxf->factory('DimStyle', array('name' => $pair['value'])));
                        break;
                    case 'UCS':
                        $this->addEntry($dxf->factory('UCS', array('name' => $pair['value'])));
                        break;
                    case 'VIEW':
                        $this->addEntry($dxf->factory('View', array('name' => $pair['value'])));
                        break;
                    case 'VPORT':
                        $this->addEntry($dxf->factory('VPort', array('name' => $pair['value'])));
                        break;
                    default:
                        print_R($this->name);
                        die("ERROR got unknown table name");
                        break;
                }
            }
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