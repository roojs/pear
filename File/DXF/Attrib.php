<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Attrib extends File_DXF_Entity
{

    public $subclasses = array();

    public $mText;

    function parse($dxf)
    {
        // parse common pair for entities
        $this->parseCommon($dxf);

        while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:
                    if ($pair['value'] != "MTEXT") {
                        // End of this entity
                        $dxf->pushPair($pair);
                        return;
                    }
                    $mText = $dxf->factory("MText")->parse($dxf);
                    break;
                case 100:
					// Beginning of a subclass
					$this->subclasses[$pair['value']] = $dxf->factory($pair['value'])->parse($dxf);
					break;
                default:
                    $groupCode = $pair['key'];
                    throw new Exception ("Got unknown group code ($groupCode)");
                    break;
            }
        }
    }
}
