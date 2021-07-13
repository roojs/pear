<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbMText extends File_DXF_Subclass
{

    function parseToEntity($dxf, $entity)
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
                    $entity->insertionPointX = $pair['value'];
                    break;
                case 20:
                    $entity->insertionPointY = $pair['value'];
                    break;
                case 30:
                    $entity->insertionPointZ = $pair['value'];
                    break;
                case 40:
                    $entity->nominalTextHeight = $pair['value'];
                    break;
                case 41:
                    $entity->referenceRectangleWidth = $pair['value'];
                    break;
                case 46:
                    $entity->annotationHeight = $pair['value'];
                    break;
                case 71:
                    $entity->attachmentPoint = $pair['value'];
                    break;
                case 72:
                    $entity->drawingDirection = $pair['value'];
                    break;
                case 1:
                case 3:
                    if (!isset($entity->textString)) {
                        $entity->textString = "";
                    }
                    $entity->textString .= $pair['value'];
                    break;
                case 7:
                    $entity->x = $pair['value'];
                    break;
                case 210:
                    $entity->extrusionDirectionX = $pair['value'];
                    break;
                case 220:
                    $entity->extrusionDirectionY = $pair['value'];
                    break;
                case 230:
                    $entity->extrusionDirectionZ = $pair['value'];
                    break;
                case 11:
                    $entity->xAxisDirecitonVectorX = $pair['value'];
                    break;
                case 21:
                    $entity->xAxisDirecitonVectorY = $pair['value'];
                    break;
                case 31:
                    $entity->xAxisDirecitonVectorZ = $pair['value'];
                    break;
                case 42:
                    $entity->width = $pair['value'];
                    break;
                case 43:
                    $entity->height = $pair['value'];
                    break;
                case 50:
                    $entity->rotationAngle = $pair['value'];
                    break;
                case 73:
                    $entity->lineSpacingStyle = $pair['value'];
                    break;
                case 44:
                    $entity->lineSpacingFactor = $pair['value'];
                    break;
                case 90:
                    $entity->backgroundFill = $pair['value'];
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
                    $entity->backgroundColor = $pair['value'];
                    break;
                case 45:
                    $entity->fillBoxScale = $pair['value'];
                    break;
                case 63:
                    $entity->backgroundFillColor = $pair['value'];
                    break;
                case 441:
                    $entity->transparency = $pair['value'];
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for subclass AcDbMText ($pairString)");
                    break;
            }
        }
    }
}