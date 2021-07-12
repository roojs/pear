<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Attrib extends File_DXF_Entity
{

    public $subclasses = array();

    // For subclass Attribute
    public $attributeSubclassMarker; // 100
    public $VersionNumber = 0; // 280
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

    // For subclass XRecord
    public $xrecordSubclassMarker; // 100
    public $duplicateRecordCloingFlag; // 280
    public $numberOfAttributeDefinitions; // 70
    public $hardPointerOfAttributeDefinition; // 340
    public $attributeDefinitionAlignmentPointX; // 10
    public $xrecordInsertionPointY; // 20
    public $xrecordInsertionPointZ; // 30
    public $annotationScale; // 40
    public $attributeDefinitionTagString; // 2

    function parse($dxf)
    {
        // parse common pair for entities
        $this->parseCommon($dxf);

        while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:
                    // End of this entity
                    $dxf->pushPair();
                    return;
                case 100:
					// Beginning of a subclass
					$this->subclasses[$pair['value']] = $dxf->factory($pair['value'])->parse($dxf);
					break;
                default:
                    $groupCode = $pair['key'];
                    throw new Exception ("Got unknown group code ($groupCode)");
                    break;
            }
        }
    }
}
