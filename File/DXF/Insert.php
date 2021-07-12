<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Insert extends File_DXF_Entity
{

    public $subclasses = array();

    public $attributes = array();

    function parse($dxf)
    {
        // parse common pair for entities
        $this->parseCommon($dxf);
        
        while($pair = $dxf->readPair()) {

            switch($pair['key']) { 
                case 0:

                    if ($this->subclasses["AcDbBlockReference"]->hasAttribute == 0) {
                        // No attributes follow
                        // End of this entity
                        $dxf->pushPair($pair); 
                        return;
                    }

                    if ($pair['value'] == "ATTRIB") {
                        // An attribute
                        $attributes[] = $dxf->factory("Attrib")->parse($dxf);
                        break;
                    }

                    if ($pair['value'] == "SEQEND") {
                        // No more attributes
                        $dxf->factorys("Seqend")->parse($dxf);
                        return;
                    } 
                    throw new Exception ("Got invalid pair within an insert entity ($pair)");
                    break;
                case 100:
                    // Beginning of a subclass
					$this->subclasses[$pair['value']] = $dxf->factory($pair['value'])->parse($dxf);
					break;
                default:
                    $groupCode = $pair['key'];
                    throw new Exception ("Got unknown group code for entity INSERT ($groupCode)");
                    break;
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
