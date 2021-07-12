<?php

require_once 'File/DXF/Entity.php';

class File_DXF_EndBlk extends File_DXF_Entity
{

    function parse($dxf)
    {
        // parse common pair for entities
        $this->parseCommon($dxf);

        while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:
                    // End of this entity
                    $dxf->pushPair($pair);
                    return;
                case 100:
					// Beginning of a subclass
					$subclass = $dxf->factory($pair['value']);
					$subclass->parse($dxf);
					$this->subclasses[$pair['value']] = $subclass;
					break;
                case 1001:
                    $applicationGroup = $dxf->factory("ApplicationGroup", array("applicationName" => $pair['value']));
                    $applicationGroup->parse($dxf);
                    $this->extendedData[] = $applicationGroup;
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for entity ENDBLK ($pairString)");
                    break;
            }
        }
    }

    /*
     * OLD CODE BELOW
     */

    /**
     * Endblk constructor.
     * @param $layer
     * @param $pointer
     */
    /*
    function __construct($layer, $pointer)
    {
        $this->entityType = 'endblk';
        $this->layer = $layer;
        $this->pointer = $pointer;
        parent::__construct();
    }
    */

    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    /*
    public function render()
    {
        $output = parent::render();
        array_push($output, 100, 'AcDbBlockEnd');
        return implode(PHP_EOL, $output);
    }
    */
}
