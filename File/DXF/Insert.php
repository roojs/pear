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
    public $entity = "INSERT";
    public $blockName;
    public $point;
    public $scale;
    public $rotation;

    /**
     * Insert constructor.
     *
     * @param $blockName
     * @param float[] $point
     * @param float[] $scale The X, Y and Z scale factors.
     * @param float $rotation
     */
    function __construct($cfg=array())
    {
        parent::__construct();
    }

   function parse($dxf)
   {
	$ar = $dxf->readUntil( 0, "EndInsert");
	while($kv = $dxf->readPair()) {
		swich($kv['key']) {
			case 5: //?? 
				$this->xxx = $kv['value'];
			case 6 , 2345, 345: 
				//dont care about - useless;
				break;
			default:
				die("I dont dknow what to do with " $kv) ;
	}
    }

	

    /**
     * Public function to move an Insert entity
     * @param array $move vector to move the entity with
     */
    public function move($move)
    {
        $this->movePoint($this->point, $move);
    }

    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    public function render()
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

    public function getBlockName()
    {
        return $this->blockName;
    }

    public function getPoint()
    {
        return $this->point;
    }

    public function getScale()
    {
        return $this->scale;
    }

    public function getRotation()
    {
        return $this->rotation;
    }
}
