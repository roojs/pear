<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_ApplicationGroup extends File_DXF_BasicObject
{
    
    public $applicationName; // 1001

    public $items = array();

    function parse($dxf)
    {

        while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:
                case 1001:
                    // End of a registered application group
                    $dxf->pushPair($pair);
                    break;
                case 1000:
                    $this->items[] = array("String" => $pair['value']);
                    return;
                case 1002:
                    $this->items[] = array("Control string" => $pair['value']);
                    break;
                case 1003:
                    $this->items[] = array("Layer name" => $pair['value']);
                    break;
                case 1004:
                    $this->items[] = array("Binary data" => $pair['value']);
                    break;
                case 1005:
                    $this->items[] = array("Database handle" => $pair['value']);
                    break;
                case 1010:
                    $this->items[] = array("Point X" => $pair['value']);
                    break;
                case 1020:
                    $this->items[] = array("Point Y" => $pair['value']);
                    break;
                case 1030:
                    $this->items[] = array("Point Z" => $pair['value']);
                    break;
                case 1011:
                    $this->items[] = array("World space position X" => $pair['value']);
                    break;
                case 1021:
                    $this->items[] = array("World space position Y" => $pair['value']);
                    break;
                case 1031:
                    $this->items[] = array("World space position Z" => $pair['value']);
                    break;
                case 1012:
                    $this->items[] = array("World space displacement X" => $pair['value']);
                    break;
                case 1022:
                    $this->items[] = array("World space displacement Y" => $pair['value']);
                    break;
                case 1032:
                    $this->items[] = array("World space displacement Z" => $pair['value']);
                    break;
                case 1013:
                    $this->items[] = array("World direction X" => $pair['value']);
                    break;
                case 1023:
                    $this->items[] = array("World direction Y" => $pair['value']);
                    break;
                case 1033:
                    $this->items[] = array("World direction Z" => $pair['value']);
                    break;
                case 1040:
                    $this->items[] = array("Real" => $pair['value']);
                    break;
                case 1041:
                    $this->items[] = array("Distance" => $pair['value']);
                    break;
                case 1042:
                    $this->items[] = array("Scale factor" => $pair['value']);
                    break;
                case 1070:
                    $this->items[] = array("Integer" => $pair['value']);
                    break;
                case 1071:
                    $this->items[] = array("Long" => $pair['value']);
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for extended data ($pairString)");
                    break;
            }

        }
    }
}