<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Attrib extends File_DXF_Entity
{
    // For subclass AcDbText
    public $thickness = 0; // 39
    public $textRotation = 0; // 50
    public $scaleX = 1; // 41
    public $obliqueAngle = 0; // 51
    public $textStyleName = "STANDARD"; // 7
    public $textGenerationFlags = 0; // 71
    public $horizontalTextJustificationType = 0; // 72
    public $extrusionDirectionX = 0; // 210
    public $extrusionDirectionY = 0; // 220
    public $extrusionDirectionZ = 1; // 230
    public $verticalTextJustificationType = 0; // 73

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
