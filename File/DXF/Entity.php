<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_Entity extends File_DXF_BasicObject
{
	/*
	 * public $XXX // (Group code)
	 */

	public $entityName; // -1
	public $entityType; // 0
	public $handle; // 5
	public $softPointerToOwnerDictionary; // 330
	public $hardPointerToOwnerDictionary; // 360
	public $softPointerToOwnerBlockRecord; // 330
	public $subclassMaker; // 100
	public $layoutTabName; // 410
	public $layerName; // 8
	public $linetypeName; // 6
	public $hardPointerToMaterial; // 347
	public $colorNumber; //62
	public $lineweightEnum; // 370
	public $linetypeScale; // 48
	public $objectVisibility; // 60
	public $proxyEntityGraphicsBytes; // 92
	public $proxyEntityGraphicsData; // 310
	public $colorValue; // 420
	public $colorName; // 430
	public $TransparencyValue; // 440
	public $hardPointerToPlotStyle; // 309
	public $shadowMode; // 284


	function __construct($cfg=array()) 
	{
		$this->entityType = strtoupper(str_replace("File_DXF_", "", get_class($this)));
		parent::__construct($cfg=array());
	}

    /*
     * OLD CODE BELOW
     */

	// protected $entityType;
	// protected $layer = 0;
	// protected $flags;
	// protected $pointer = 0;
	// protected $color = 256;
	// protected $paperSpace = 0;
	// protected $lineType = 'BYLAYER';	// protected $entityType;
	// protected $layer = 0;
	// protected $flags;
	// protected $pointer = 0;
	// protected $color = 256;
	// protected $paperSpace = 0;
	// protected $lineType = 'BYLAYER';
	// protected $lineTypeScale = 1;
	// protected $hidden = 0;

	/**
	 * protected move function
	 * Move a point with a given move vector
	 *
	 * @param $point array
	 * @param $move array
	 */
	/*
	protected function movePoint(&$point, $move) {
		for($i = 0; $i < count($move); $i++) {
		if(!isset($point[$i])) {
			$point[$i] = 0;
		}
		$point[$i] += $move[$i];
		}
	}
	*/

	/**
	 * protected rotate function
	 * Rotate one point around a center point with an angle
	 *
	 * @param $point array
	 * @param $center array
	 * @param $angle float
	 */
	/*
	protected function rotatePoint(&$point, $center, $angle) {
		$xPos = $point[0];
		$yPos = $point[1];
		$point[0] = $center[0] + ($xPos - $center[0]) * cos($angle) - ($yPos - $center[1]) * sin($angle);
		$point[1] = $center[1] + ($yPos - $center[1]) * cos($angle) + ($xPos - $center[0]) * sin($angle);
	}
	*/

	/**
	 * protected function for rendering the flags
	 *
	 * @return int|number
	 */
	/*
	protected function flagsToString() {
		$output = 0;
		foreach ($this->flags as $i => $flag) {
		$output += pow(2, $i) * $flag;
		}
		return $output;
	}
	*/

	/**
	 * Public function to set flag values for entities
	 *
	 * @param $id int
	 * @param $value 0|1
	 */
	/*
	public function setFlag($id, $value) {
		$this->flags[$id] = $value;
		return $this;
	}
	*/

	/**
	 * Retrieves a flag value for an entity.
	 */
	/*
	public function getFlag($id) {
		return $this->flags[$id];
	}
	*/

	/**
	 * Public function to set the layer of an entity
	 *
	 * @param $layer string
	 */
	/*
	public function setLayer($layer) {
		$this->layer = $layer;
		return $this;
	}
	*/

	/**
	 * Public function to set the color of an entity
	 *
	 * @param $color int autodesc color code 0 = BYBLOCK, 256 = BYLAYER
	 */
	/*
	public function setColor($color) {
		$this->color = $color;
		return $this;
	}
	*/

	/**
	 * Public function to define if a object should belong to paper space
	 *
	 * @param $ps 0|1
	 */
	/*
	public function setPaperSpace($ps) {
		$this->paperSpace = $ps;
		return $this;
	}
	*/

	/**
	 * Public function to set the lineType of an entity and an optional scale
	 *
	 * @param $lineType
	 * @param int $scale float
	 */
	/*
	public function setLineType($lineType, $scale = 1) {
		$this->lineType = $lineType;
		$this->lineTypeScale = $scale;
		return $this;
	}
	*/

	/**
	 * Public function to hide an entity
	 *
	 * @param $hidden
	 */
	/*
	public function hide($hidden) {
		$this->hidden = $hidden;
		return $this;
	}
	*/

	/**
	 * Public function to render an entity, returns a string representation of
	 * the entity.
	 * @return array
	 */
	/*
	function render() {
		$output = array();
		array_push($output, 0, strtoupper($this->entityType));
		array_push($output, 5, $this->getHandle());
		array_push($output, 330, $this->idToHex($this->pointer));
		array_push($output, 100, 'AcDbEntity');
		array_push($output, 67, $this->paperSpace);
		array_push($output, 8, $this->layer);
		array_push($output, 6, $this->lineType);
		array_push($output, 62, $this->color);
		array_push($output, 48, $this->lineTypeScale);
		array_push($output, 60, $this->hidden);
		return $output;
	}
	*/
}
