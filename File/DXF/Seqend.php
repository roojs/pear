<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Seqend extends File_DXF_Entity 
{
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
