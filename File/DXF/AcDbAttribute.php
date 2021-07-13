<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbAttribute extends File_DXF_Subclass
{
    
    public $attributeTag; // 2
    public $attributeFlags; // 70
    public $fieldLength = 0; // 73
    public $textRotation = 0; // 50
    public $scaleX = 1; // 41
    public $obliqueAngle = 0; // 51
    public $textStyleName = "STANDARD"; // 7
    public $textGenerationFlags = 0; // 71
    public $horizontalTextJustificationType = 0; // 72
    public $verticalTextJustificationType = 0; // 74
    public $alignmentPointX; // 11
    public $alignmentPointY; // 21
    public $alignmentPointZ; // 31
    public $extrusionDirectionX = 0; // 210
    public $extrusionDirectionY = 0; // 220
    public $extrusionDirectionZ = 1; // 230
    public $lockPositionFlag; // 280

    function parse($dxf)
    {
        
        while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:
                case 100:
                case 1001:
                    // End of a subclass
                    $dxf->pushPair($pair);
                    return;
                    break;
                case 2:
                    $this->attributeTag = $pair['value'];
                    break;
                case 70:
                    $this->attributeFlags = $pair['value'];
                    break;
                case 73:
                    $this->fieldLength = $pair['value'];
                    break;
                case 50:
                    $this->textRotation = $pair['value'];
                    break;
                case 41:
                    $this->scaleX = $pair['value'];
                    break;
                case 51:
                    $this->obliqueAngle = $pair['value'];
                    break;
                case 7:
                    $this->textStyleName = $pair['value'];
                    break;
                case 71:
                    $this->textGenerationFlags = $pair['value'];
                    break;
                case 72:
                    $this->horizontalTextJustificationType = $pair['value'];
                    break;
                case 74:
                    $this->verticalTextJustificationType = $pair['value'];
                    break;
                case 11:
                    $this->alignmentPointX = $pair['value'];
                    break;
                case 21:
                    $this->alignmentPointY = $pair['value'];
                    break;
                case 31:
                    $this->alignmentPointZ = $pair['value'];
                    break;
                case 210:
                    $this->extrusionDirectionX = $pair['value'];
                    break;
                case 220:
                    $this->extrusionDirectionY = $pair['value'];
                    break;
                case 230:
                    $this->extrusionDirectionZ = $pair['value'];
                    break;
                case 280:
                    $this->lockPositionFlag = $pair['value'];
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for subclass AcDbAttribute ($pairString)");
                    break;
            }
        }
    }
}