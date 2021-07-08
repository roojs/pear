<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_Entity extends File_DXF_BasicObject
{
	public $entityType;
	public $layer = 0;
	public $flags;
	public $pointer = 0;
	public $color = 256;
	public $paperSpace = 0;
	public $lineType = 'BYLAYER';
	public $lineTypeScale = 1;
	public $hidden = 0;

	/**
	 * public move function
	 * Move a point with a given move vector
	 *
	 * @param $point array
	 * @param $move array
	 */
	function movePoint(&$point, $move)
	{
		for ($i = 0; $i < count($move); $i++) {
			if (!isset($point[$i])) {
				$point[$i] = 0;
			}
			$point[$i] += $move[$i];
		}
	}

	/**
	 * public rotate function
	 * Rotate one point around a center point with an angle
	 *
	 * @param $point array
	 * @param $center array
	 * @param $angle float
	 */
	function rotatePoint(&$point, $center, $angle)
	{
		$xPos = $point[0];
		$yPos = $point[1];
		$point[0] = $center[0] + ($xPos - $center[0]) * cos($angle) - ($yPos - $center[1]) * sin($angle);
		$point[1] = $center[1] + ($yPos - $center[1]) * cos($angle) + ($xPos - $center[0]) * sin($angle);
	}

	/**
	 * public function for rendering the flags
	 *
	 * @return int|number
	 */
	function flagsToString()
	{
		$output = 0;
		foreach ($this->flags as $i => $flag) {
			$output += pow(2, $i) * $flag;
		}
		return $output;
	}

	/**
	 * Public function to set flag values for entities
	 *
	 * @param $id int
	 * @param $value 0|1
	 */
	function setFlag($id, $value)
	{
		$this->flags[$id] = $value;
		return $this;
	}

	/**
	 * Retrieves a flag value for an entity.
	 */
	function getFlag($id)
	{
		return $this->flags[$id];
	}

	/**
	 * Public function to set the lineType of an entity and an optional scale
	 *
	 * @param $lineType
	 * @param int $scale float
	 */
	function setLineType($lineType, $scale = 1)
	{
		$this->lineType = $lineType;
		$this->lineTypeScale = $scale;
		return $this;
	}

	/**
	 * Public function to hide an entity
	 *
	 * @param $hidden
	 */
	public function hide($hidden)
	{
		$this->hidden = $hidden;
		return $this;
	}

	/**
	 * Public function to render an entity, returns a string representation of
	 * the entity.
	 * @return array
	 */
	function render()
	{
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
}
