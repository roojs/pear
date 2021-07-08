<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_Table extends File_DXF_BasicObject
{
    public $name;
    public $data = array();
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

            if ($pair['key'] == 0) {
                if ($pair['value'] == "ENDTAB") {
                    // End of a table
                    return;
                }
                if ($pair['value'] == $this->name) {
                    // Beginning of a new table entry
                    switch ($this->name) {
                        case 'LTYPE':
                            $entry = $dxf->factory('LType');
                            break;
                        case 'STYLE':
                            $entry = $dxf->factory('Style');
                            break;
                        case 'LAYER':
                            $entry = $dxf->factory('Layer');
                            break;
                        case 'APPID':
                            $entry = $dxf->factory('AppID');
                            break;
                        case 'BLOCK_RECORD':
                            $entry = $dxf->factory('BlockRecord');
                            break;
                        case 'DIMSTYLE':
                            $entry = $dxf->factory('DimStyle');
                            break;
                        case 'UCS':
                            $entry = $dxf->factory('UCS');
                            break;
                        case 'VIEW':
                            $entry = $dxf->factory('View');
                            break;
                        case 'VPORT':
                            $entry = $dxf->factory('VPort');
                            break;
                        default:
                            print_R($this->name);
                            die("ERROR got unknown table name");
                            break;
                    }

                    $entry->parse($dxf);
                    $this->addEntry($entry);
                }
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