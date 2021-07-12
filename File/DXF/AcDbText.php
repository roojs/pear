<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbText extends File_DXF_Subclass
{
    
    public $thickness = 0; // 39
    public $textStartPointX; // 10
    public $textStartPointY; // 20
    public $textStartPointZ; // 30
    public $textHeight; // 40
    public $defaultValue; // 1

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
                    $this->defaultValue = $pair['value'];
                    break;
                default:
                    $groupCode = $pair['key'];
                    throw new Exception ("Got unknown group code for subclass AcDbText ($groupCode)");
                    break;
            }
        }
    }
}