<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbMText extends File_DXF_Subclass
{

    public $insertionPointX; // 10
    public $insertionPointY; // 20
    public $insertionPointZ; // 30
    public $nominalTextHeight; // 40
    public $referenceRectangleWidth; // 41
    public $annotationHeight; // 46
    public $attachmentPoint; // 71
    public $drawingDirection; // 72
    public $textString = ""; // 1: (< 250 characters) / 3: (= 250 characters)
    public $x; // 7
    public $extrusionDirectionX; // 210
    public $extrusionDirectionY; // 220
    public $extrusionDirectionZ; // 230
    public $xAxisDirecitonVectorX; // 11
    public $xAxisDirecitonVectorY; // 21
    public $xAxisDirecitonVectorZ; // 31
    public $width; // 42
    public $height; // 43
    public $rotationAngle; // 50
    public $lineSpacingStyle; // 73
    public $lineSpacingFactor; // 44
    public $backgroundFill; // 90
    public $backgroundColor; // 63 / 420 - 429 / 430 -439
    public $fillBoxScale; // 45
    public $backgroundFillColor; // 63
    public $transparency; // 441

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
                case 10:
                    $this->insertionPointX = $pair['value'];
                    break;
                case 20:
                    $this->insertionPointY = $pair['value'];
                    break;
                case 30:
                    $this->insertionPointZ = $pair['value'];
                    break;
                case 40:
                    $this->nominalTextHeight = $pair['value'];
                    break;
                case 41:
                    $this->referenceRectangleWidth = $pair['value'];
                    break;
                case 46:
                    $this->annotationHeight = $pair['value'];
                    break;
                case 71:
                    $this->attachmentPoint = $pair['value'];
                    break;
                case 72:
                    $this->drawingDirection = $pair['value'];
                    break;
                case 1:
                case 3:
                    if (!isset($this->textString)) {
                        $this->textString = "";
                    }
                    $this->textString .= $pair['value'];
                    break;
                case 7:
                    $this->x = $pair['value'];
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
                case 11:
                    $this->xAxisDirecitonVectorX = $pair['value'];
                    break;
                case 21:
                    $this->xAxisDirecitonVectorY = $pair['value'];
                    break;
                case 31:
                    $this->xAxisDirecitonVectorZ = $pair['value'];
                    break;
                case 42:
                    $this->width = $pair['value'];
                    break;
                case 43:
                    $this->height = $pair['value'];
                    break;
                case 50:
                    $this->rotationAngle = $pair['value'];
                    break;
                case 73:
                    $this->lineSpacingStyle = $pair['value'];
                    break;
                case 44:
                    $this->lineSpacingFactor = $pair['value'];
                    break;
                case 90:
                    $this->backgroundFill = $pair['value'];
                    break;
                case 63:
                case 420:
                case 421:
                case 422:
                case 423:
                case 424:
                case 425:
                case 426:
                case 427:
                case 428:
                case 429:
                case 430:
                case 431:
                case 432:
                case 433:
                case 434:
                case 435:
                case 436:
                case 437:
                case 438:
                case 439:
                    $this->backgroundColor = $pair['value'];
                    break;
                case 45:
                    $this->fillBoxScale = $pair['value'];
                    break;
                case 63:
                    $this->backgroundFillColor = $pair['value'];
                    break;
                case 441:
                    $this->transparency = $pair['value'];
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for subclass AcDbMText ($pairString)");
                    break;
            }
        }
    }
}