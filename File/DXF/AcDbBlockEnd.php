<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbBlockEnd extends File_DXF_Subclass
{

    function parseToEntity($dxf)
    {
        while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:
                case 100:
                case 1001:
                    // End of a subclass
                    $dxf->pushPair($pair);
                    return;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for subclass AcDbBlockEnd ($pairString)");
                    break;
            }
        }
    }
}