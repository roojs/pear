<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Attrib extends File_DXF_Entity
{
    public $entityType = 'ATTRIB';
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
}
