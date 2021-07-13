<?php

require_once 'File/DXF/Entity.php';

class File_DXF_EndBlk extends File_DXF_Entity
{

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
