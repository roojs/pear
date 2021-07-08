<?php

/**
 * Created by PhpStorm.
 * User: jpietler
 * Date: 30.12.16
 * Time: 16:18
 *
 * Dokumentation http://www.autodesk.com/techpubs/autocad/acad2000/dxf/seqend_dxf_06.htm
 * This is baed on DXF Fighter by - https://github.com/enjoping/DXFighter
 */


/**
 * Class Seqend
 * @package DXFighter\lib
 */
require_once 'File/DXF/Entity.php';

class File_DXF_Seqend extends File_DXF_Entity
{

    public $entityType = 'SEQEND';

    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    function render()
    {
        $output = parent::render();
        return implode(PHP_EOL, $output);
    }
}
