<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbText extends File_DXF_Subclass
{

    function parseToEntity($dxf)
    {
        
        while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:
                case 100:
                case 1001:
                    // End of a subclass
                    $dxf->pushPair($pair);
                    return;
                case 39:
                    $this->thickness = $pair['value'];
                    break;
                case 10:
                    $this->textStartPointX = $pair['value'];
                    break;
                case 20:
                    $this->textStartPointY = $pair['value'];
                    break;
                case 30:
                    $this->textStartPointZ = $pair['value'];
                    break;
                case 40:
                    $this->textHeight = $pair['value'];
                    break;
                case 1:
                    $this->value = $pair['value'];
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
                case 73:
                    $this->verticalTextJustificationType = $pair['value'];
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for subclass AcDbText ($pairString)");
                    break;
            }
        }
    }
}