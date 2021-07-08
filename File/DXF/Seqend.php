<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Seqend extends File_DXF_Entity
{
    public $entityType = 'SEQEND';

    function parse($dxf)
    {
         while($pair = $dxf->readPair()) {
            if ($pair['key'] == 0) {
                // End of this entity
                // Beginning of a new entity
                return $pair;
            }
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
