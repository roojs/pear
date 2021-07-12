<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Seqend extends File_DXF_Entity 
{

    public $beginSequenceEntityName;

    function parse($dxf)
    {
        // parse common pair for entities
        $this->parseCommon($dxf, false);

        while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:
                    // End of this entity
                    $dxf->pushPair($pair);
                    return;
                case -2:
                    $this->beginSequenceEntityName = $pair['value'];
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for entity INSERT ($pairString)");
                    break;
            }
    
        }
    }

    /*
     * OLD CODE BELOW
     */

    /**
     * Seqend constructor.
     * @param $pointer
     * @param $layer
     */
    /*
    function __construct($pointer, $layer) {
        $this->entityType = 'seqend';
        $this->pointer = $pointer;
        $this->layer = $layer;
        parent::__construct();
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
        return implode(PHP_EOL, $output);
    }
    */
}
