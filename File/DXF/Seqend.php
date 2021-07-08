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
    public $data = array();

    function parse($dxf)
    {
         while($pair = $dxf->readPair()) {
            if ($pair['key'] == 0) {
                // End of this entity
                // Beginning of a new entity
                return $pair;
            }
            $this->data[$pair['key']] = $this->data[$pair['value']];
        }
    }

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
