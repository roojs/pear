<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbBlockReference extends File_DXF_Subclass
{

    function parse($dxf)
    {
        
        while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:
                case 100:
                case 1001:
                    // End of this subclass
                    $dxf->pushPair($pair);
                    return;
                case 66:
                    $this->hasAttribute = $pair['value'];
                    break;
                case 2:
                    $this->blockName = $pair['value'];
                    break;
                case 10:
                    $this->insertionPointX = $pair['value'];
                    break;
                case 20:
                    $this->insertionPointY = $pair['value'];
                    break;
                case 30:
                    $this->insertionPointZ = $pair['value'];
                    break;
                case 41:
                    $this->scaleX = $pair['value'];
                    break;
                case 42:
                    $this->scaleY = $pair['value'];
                    break;
                case 43:
                    $this->scaleZ = $pair['value'];
                    break;
                case 50:
                    $this->rotation = $pair['value'];
                    break;
                case 70:
                    $this->columnCount = $pair['value'];
                    break;
                case 71:
                    $this->rowCount = $pair['value'];
                    break;
                case 44:
                    $this->columnSpacing = $pair['value'];
                    break;
                case 45:
                    $this->rowSpacing = $pair['value'];
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
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for subclass AcDbBlockReference ($pairString)");
                    break;
            }
        }
    }
}