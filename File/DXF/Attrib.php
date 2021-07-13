<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Attrib extends File_DXF_Entity
{
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

                    $this->mText = $dxf->factory("MText");
                    $this->mText->parse($dxf);
                    break;
                case 100:
					// Beginning of a subclass
					$dxf->factory($pair['value'])->parseToEntity($dxf, $this);
					break;
                case 1001:
                    $this->skipParseExtendedData($dxf);
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for entity ATTRIB ($pairString)");
                    break;
            }
        }
    }
}
