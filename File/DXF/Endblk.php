<?php

/**
 * Created by PhpStorm.
 * User: jpietler
 * Date: 30.12.16
 * Time: 16:18
 *
 * Dokumentation http://www.autodesk.com/techpubs/autocad/acad2000/dxf/endblk_dxf_05.htm
 * This is baed on DXF Fighter by - https://github.com/enjoping/DXFighter"
 */


/**
 * Class Endblk
 * @package DXFighter\lib
 */
require_once 'File/DXF/Entity.php';

class File_DXF_Endblk extends File_DXF_Entity
{

    /**
     * Endblk constructor.
     * @param $layer
     * @param $pointer
     */
    function __construct($layer, $pointer)
    {
        $this->entityType = 'endblk';
        $this->layer = $layer;
        $this->pointer = $pointer;
        parent::__construct();
    }

    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    public function render()
    {
        $output = parent::render();
        array_push($output, 100, 'AcDbBlockEnd');
        return implode(PHP_EOL, $output);
    }
}
