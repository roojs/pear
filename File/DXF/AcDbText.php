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
    public $textRotation = 0; // 50
    public $scaleX = 1; // 41
    public $obliqueAngle = 0; // 51
    public $textStyleName = "STANDARD"; // 7
    public $textGenerationFlags = 0; // 71
    public $horizontalTextJustificationType = 0; // 72
    public $alignmentPointX; // 11
    public $alignmentPointY; // 21
    public $alignmentPointZ; // 31
    public $extrusionDirectionX = 0; // 210
    public $extrusionDirectionY = 0; // 220
    public $extrusionDirectionZ = 1; // 230
    public $verticalTextJustificationType = 0; // 73

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
                case 7:
                    $this->textStyleName = $pair['value'];
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for subclass AcDbText ($pairString)");
                    break;
            }
        }
    }
}