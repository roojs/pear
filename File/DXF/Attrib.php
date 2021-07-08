<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Attrib extends File_DXF_Entity
{
    public $entityType = 'ATTRIB';
    public $data = array();

    function parse($dxf)
    {
         while($pair = $dxf->readPair()) {
             switch($pair['key']) {
                case 0:
                    if ($pair['key'] == "SEQEND"){
                        return $pair;
                    }
                    break;
                default:
                    $this->data[$pair['key']] = $this->data[$pair['value']];
                    break;
            }
         }
     }
}
