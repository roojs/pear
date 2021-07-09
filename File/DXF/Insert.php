<?php

/**
 * Created by PhpStorm.
 * User: jpietler
 * Date: 13.02.20
 * Time: 20:57
 *
 * Documentation https://www.autodesk.com/techpubs/autocad/acad2000/dxf/insert_dxf_06.htm
 * This is baed on DXF Fighter by - https://github.com/enjoping/DXFighter"
 */


/**
 * Class Circle
 * @package DXFighter\lib
 */
require_once 'File/DXF/Entity.php';

class File_DXF_Insert extends File_DXF_Entity
{

    public $subclassMarker; // 100
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

    public $attributes = array();

    function parse($dxf)
    {
        // parse common pair for entities
        $this->parseCommon($dxf);
        
        while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:

                    if ($this->hasAttribute == 0) {
                        // No attributes follow
                        // End of this entity
                        $dxf->pushPair($pair);
                        return;
                    }

                    if ($pair['value'] == "ATTRIB") {
                        // An attribute
                        $attributes[] = $dxf->factory("Attrib")->parse($dxf);
                    }

                    if ($pair['value'] == "SEQEND") {
                        // No more attributes
                        $dxf->factorys("Seqend")->parse($dxf);
                        return;
                    } 
                    throw new Exception ("Got invalid pair within an insert entity ($pair)");
                    break;
                    
                case 100:
                    $this->subclassMarker = $pair['value'];
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
                    throw new Exception ("Got unknown group code ($groupCode)");
            }
        }
    }

    /*
     * OLD CODE BELOW
     */

    // protected $blockName;
    // protected $point;
    // protected $scale;
    // protected $rotation;

    /**
     * Insert constructor.
     *
     * @param $blockName
     * @param float[] $point
     * @param float[] $scale The X, Y and Z scale factors.
     * @param float $rotation
     */
    /*
    function __construct( $blockName, $point = [0, 0, 0], $scale = [1, 1, 1], $rotation = 0) {
        $this->entityType = 'insert';
        $this->blockName       = $blockName;
        $this->point      = $point;
        $this->scale      = $scale;
        $this->rotation   = $rotation;
        parent::__construct();
    }
    */

    /**
     * Public function to move an Insert entity
     * @param array $move vector to move the entity with
     */
    /*
    public function move($move) {
        $this->movePoint($this->point, $move);
    }
    */

    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    /*
    public function render() {
        $output = parent::render();
        array_push($output, 100, 'AcDbBlockReference');
        array_push($output, 2, strtoupper($this->blockName));
        array_push($output, $this->point($this->point));
        array_push($output, 41, $this->scale[0]);
        array_push($output, 42, $this->scale[1]);
        array_push($output, 43, $this->scale[2]);
        array_push($output, 50, $this->rotation);
        return implode(PHP_EOL, $output);
    }

    public function getBlockName() {
        return $this->blockName;
    }

    public function getPoint() {
        return $this->point;
    }

    public function getScale() {
        return $this->scale;
    }

    public function getRotation() {
        return $this->rotation;
    }
    */
}
