<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbBlockReference extends File_DXF_Subclass
{
    public $hasAttribute = 0; // 66
    public $blockName; // 2
    public $insertionPointX; // 10
    public $insertionPointY; // 20
    public $insertionPointZ; // 30
    public $scaleX = 1; // 41
    public $scaleY = 1; // 42
    public $scaleZ = 1; // 43
    public $rotation = 0; // 50
    public $columnCount = 1; // 70
    public $rowCount = 1; // 71
    public $columnSpacing = 0; // 44
    public $rowSpacing = 0; // 45
    public $extrusionDirectionX = 0; // 210
    public $extrusionDirectionY = 0; // 220
    public $extrusionDirectionZ = 1; // 230

    function parse($dxf)
    {
        
        while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:
                case 100:
                    // End of a subclass
                    $dxf->pushPair($pair);
                    return;
                    break;
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
                    $groupCode = $pair['key'];
                    $value = $pair['value'];
                    throw new Exception ("Got unknown group code for subclass AcDbBlockReference ($groupCode, $value)");
                    break;
            }
        }
    }
}