<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Attrib extends File_DXF_Entity
{
    public $data = array();

    function parse($dxf)
    {
         while($pair = $dxf->readPair()) {
            if ($pair['key'] == 0) {
                // End of this entity
                // Beginning of a new entity
                $dxf->pushPair();
                return $pair;
            }
            $this->data[$pair['key']] = $pair['value'];
        }
    }
}
