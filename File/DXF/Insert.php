<?php

/**
 * Created by PhpStorm.
 * User: jpietler
 * Date: 13.02.20
 * Time: 20:57
 *
 * Documentation https://www.autodesk.com/techpubs/autocad/acad2000/dxf/insert_dxf_06.htm
 * This is baed on DXF Fighter by - https://github.com/enjoping/DXFighter"
 */


/**
 * Class Circle
 * @package DXFighter\lib
 */
require_once 'File/DXF/Entity.php';

class File_DXF_Insert extends File_DXF_Entity
{
    public $entityType = "INSERT";
    public $data;
    public $blockName;
    public $point;
    public $scale;
    public $rotation;

   function parse($dxf)
   {
       
        while($pair = $dxf->readPair()) {
            if ($pair['key'] == 0) {
                if (!$hasAttrib) {
                    // End of this entity
                    // Beginning of a new entity
                    return $pair;
                }
                if ($pair['value'] == 'SEQEND') {
                    return;
                }
                $entity = $dxf->factory('Attrib');
                $entity->parse($dxf);
            }

            $this->data[$pair['key']] = $pair['value'];
        }
    }

	

    /**
     * Public function to move an Insert entity
     * @param array $move vector to move the entity with
     */
    function move($move)
    {
        $this->movePoint($this->point, $move);
    }

    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    function render()
    {
        $output = parent::render();
        array_push($output, 100, 'AcDbBlockReference');
        array_push($output, 2, strtoupper($this->blockName));
        array_push($output, $this->point($this->point));
        array_push($output, 41, $this->scale[0]);
        array_push($output, 42, $this->scale[1]);
        array_push($output, 43, $this->scale[2]);
        array_push($output, 50, $this->rotation);
        return implode(PHP_EOL, $output);
    }
}
