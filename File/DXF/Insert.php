<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Insert extends File_DXF_Entity
{
    // For subclass AcDbBlockReference
    public $hasAttribute = 0; // 66
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
                    
                    if ($pair['value'] == "SEQEND") {
                        // No more attributes
                        $this->skipParseEntity($dxf);
                        return;
                    }

                    if ($pair['value'] == "ATTRIB") {
                        // An attribute
                        $attribute = $dxf->factory("Attrib");
                        $attribute->parse($dxf);
                        $this->attributes[] = $attribute;
                        break;
                    }

                    $pairString = implode(", ", $pair);
                    throw new Exception ("Got unknown pair for entity INSERT ($pairString)");
                    break;
                case 100:
                    // Beginning of a subclass
                    $dxf->factory($pair['value'])->parseToEntity($dxf, $this);
                    break;
                case 1001:
                    $this->skipParseExtendedData($dxf);
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for entity INSERT ($pairString)");
                    break;
            }
        }
    }

    function getAttribute ($attributeTag) {
        $attributes = array();
        foreach ($this->attributes as $attribute) {
            if ($attribute->attributeTag == $attributeTag) {
                $attributes[] = $attribute;
            }
        }
        if (!empty($attributes)) {
            return $attributes;
        }
        return false;
    }

    function attributeToArray () {
        $result = array();
        foreach ($this->attributes as $attribute) {
            $result[$attribute->attributeTag] = $attribute->value;
        }
        if (!empty($result)) {
            return $result;
        }
        return false;
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
