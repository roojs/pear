<?php
/**
 * PHPWord
 *
 * Copyright (c) 2011 PHPWord
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPWord
 * @package    PHPWord
 * @copyright  Copyright (c) 010 PHPWord
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 * @version    Beta 0.6.3, 08.07.2011
 */


/**
 * PHPWord_Style_TableFull
 *
 * @category   PHPWord
 * @package    PHPWord_Style
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Style_TableFull 
{
	
	/**
	 * Style for first row
	 * 
	 * @var PHPWord_Style_Table
	 */
	private $firstRow = null;
	
	/**
	 * Cell Margin Top
	 * 
	 * @var int
	 */
	private $cellMarginTop = null;
	
	/**
	 * Cell Margin Left
	 * 
	 * @var int
	 */
	private $cellMarginLeft = null;
	
	/**
	 * Cell Margin Right
	 * 
	 * @var int
	 */
	private $cellMarginRight = null;
	
	/**
	 * Cell Margin Bottom
	 * 
	 * @var int
	 */
	private $cellMarginBottom = null;
	
	/**
	 * Background-Color
	 * 
	 * @var string
	 */
	private $bgColor;
	
	/**
	 * Border Top Size
	 * 
	 * @var int
	 */
	private $borderTopSize;
	
	/**
	 * Border Top Color
	 * 
	 * @var string
	 */
	private $borderTopColor;
	
	/**
	 * Border Left Size
	 * 
	 * @var int
	 */
	private $borderLeftSize;
	
	/**
	 * Border Left Color
	 * 
	 * @var string
	 */
	private $borderLeftColor;
	
	/**
	 * Border Right Size
	 * 
	 * @var int
	 */
	private $borderRightSize;
	
	/**
	 * Border Right Color
	 * 
	 * @var string
	 */
	private $borderRightColor;
	
	/**
	 * Border Bottom Size
	 * 
	 * @var int
	 */
	private $borderBottomSize;
	
	/**
	 * Border Bottom Color
	 * 
	 * @var string
	 */
	private $borderBottomColor;
	
	/**
	 * Border InsideH Size
	 * 
	 * @var int
	 */
	private $borderInsideHSize;
	
	/**
	 * Border InsideH Color
	 * 
	 * @var string
	 */
	private $borderInsideHColor;
	
	/**
	 * Border InsideV Size
	 * 
	 * @var int
	 */
	private $borderInsideVSize;
	
	/**
	 * Border InsideV Color
	 * 
	 * @var string
	 */
	private $borderInsideVColor;
	
	
	/**
	 * Create a new TableFull Font
	 */
	public function __construct($styleTable = null, $styleFirstRow = null, $styleLastRow = null) 
        {
		
		if(!is_null($styleFirstRow) && is_array($styleFirstRow)) {
			$this->firstRow = clone $this;
			
			unset($this->firstRow->firstRow);
			unset($this->firstRow->cellMarginBottom);
			unset($this->firstRow->cellMarginTop);
			unset($this->firstRow->cellMarginLeft);
			unset($this->firstRow->cellMarginRight);
			unset($this->firstRow->borderInsideVColor);
			unset($this->firstRow->borderInsideVSize);
			unset($this->firstRow->borderInsideHColor);
			unset($this->firstRow->borderInsideHSize);
			foreach($styleFirstRow as $key => $value) {
				if(substr($key, 0, 1) != '_') {
					$key = '_'.$key;
				}
				
				$this->firstRow->setStyleValue($key, $value);
			}
		}
		
		if(!is_null($styleTable) && is_array($styleTable)) {
			foreach($styleTable as $key => $value) {
				if(substr($key, 0, 1) != '_') {
					$key = '_'.$key;
				}
				$this->setStyleValue($key, $value);
			}
		}
	}
	
	/**
	 * Set style value
	 * 
	 * @param string $key
	 * @param mixed $value
	 */
	public function setStyleValue($key, $value) 
        {
		if($key == '_borderSize') {
			$this->setBorderSize($value);
		} elseif($key == '_borderColor') {
			$this->setBorderColor($value);
		} elseif($key == '_cellMargin') {
			$this->setCellMargin($value);
		} else {
			$this->$key = $value;
		}
	}
	
	/**
	 * Get First Row Style
	 * 
	 * @return PHPWord_Style_TableFull
	 */
	public function getFirstRow() 
        {
		return $this->firstRow;
	}
	
	/**
	 * Get Last Row Style
	 * 
	 * @return PHPWord_Style_TableFull
	 */
	public function getLastRow() 
        {
		return $this->_lastRow;
	}
	
	public function getBgColor() 
        {
		return $this->bgColor;
	}

	public function setBgColor($pValue = null) 
        {
	   $this->bgColor = $pValue;
	}

	/**
	 * Set TLRBVH Border Size
	 * 
	 * @param int $pValue
	 */
	public function setBorderSize($pValue = null) 
        {
		$this->borderTopSize = $pValue;
		$this->borderLeftSize = $pValue;
		$this->borderRightSize = $pValue;
		$this->borderBottomSize = $pValue;
		$this->borderInsideHSize = $pValue;
		$this->borderInsideVSize = $pValue;
	}
	
	/**
	 * Get TLRBVH Border Size
	 * 
	 * @return array
	 */
	public function getBorderSize() 
        {
		$t = $this->getBorderTopSize();
		$l = $this->getBorderLeftSize();
		$r = $this->getBorderRightSize();
		$b = $this->getBorderBottomSize();
		$h = $this->getBorderInsideHSize();
		$v = $this->getBorderInsideVSize();
		
		return array($t, $l, $r, $b, $h, $v);
	}
	
	/**
	 * Set TLRBVH Border Color
	 */
	public function setBorderColor($pValue = null) 
        {
		$this->borderTopColor = $pValue;
		$this->borderLeftColor = $pValue;
		$this->borderRightColor = $pValue;
		$this->borderBottomColor = $pValue;
		$this->borderInsideHColor = $pValue;
		$this->borderInsideVColor = $pValue;
	}
	
	/**
	 * Get TLRB Border Color
	 * 
	 * @return array
	 */
	public function getBorderColor() 
        {
		$t = $this->getBorderTopColor();
		$l = $this->getBorderLeftColor();
		$r = $this->getBorderRightColor();
		$b = $this->getBorderBottomColor();
		$h = $this->getBorderInsideHColor();
		$v = $this->getBorderInsideVColor();
		
		return array($t, $l, $r, $b, $h, $v);
	}
	
	public function setBorderTopSize($pValue = null) 
        {
		$this->borderTopSize = $pValue;
	}
	
	public function getBorderTopSize() 
        {
		return $this->borderTopSize;
	}
	
	public function setBorderTopColor($pValue = null) 
        {
		$this->borderTopColor = $pValue;
	}
	
	public function getBorderTopColor() 
        {
		return $this->borderTopColor;
	}

	public function setBorderLeftSize($pValue = null) 
        {
		$this->borderLeftSize = $pValue;
	}
	
	public function getBorderLeftSize() 
        {
		return $this->borderLeftSize;
	}
	
	public function setBorderLeftColor($pValue = null) 
        {
		$this->borderLeftColor = $pValue;
	}
	
	public function getBorderLeftColor() 
        {
		return $this->borderLeftColor;
	}
	
	public function setBorderRightSize($pValue = null) 
        {
		$this->borderRightSize = $pValue;
	}
	
	public function getBorderRightSize() 
        {
		return $this->borderRightSize;
	}
	
	public function setBorderRightColor($pValue = null) 
        {
		$this->borderRightColor = $pValue;
	}
	
	public function getBorderRightColor() 
        {
		return $this->borderRightColor;
	}
	
	public function setBorderBottomSize($pValue = null) 
        {
		$this->borderBottomSize = $pValue;
	}
	
	public function getBorderBottomSize() 
        {
		return $this->borderBottomSize;
	}
	
	public function setBorderBottomColor($pValue = null) 
        {
		$this->borderBottomColor = $pValue;
	}
	
	public function getBorderBottomColor() 
        {
		return $this->borderBottomColor;
	}
	
	public function setBorderInsideHColor($pValue = null) 
        {
		$this->borderInsideHColor = $pValue;
	}
	
	public function getBorderInsideHColor() 
        {
		return (isset($this->borderInsideHColor)) ? $this->borderInsideHColor : null;
	}
	
	public function setBorderInsideVColor($pValue = null) 
        {
		$this->borderInsideVColor = $pValue;
	}
	
	public function getBorderInsideVColor() 
        {
		return (isset($this->borderInsideVColor)) ? $this->borderInsideVColor : null;
	}
	
	public function setBorderInsideHSize($pValue = null) 
        {
		$this->borderInsideHSize = $pValue;
	}
	
	public function getBorderInsideHSize() 
        {
		return (isset($this->borderInsideHSize)) ? $this->borderInsideHSize : null;
	}
	
	public function setBorderInsideVSize($pValue = null) 
        {
		$this->borderInsideVSize = $pValue;
	}
	
	public function getBorderInsideVSize() 
        {
		return (isset($this->borderInsideVSize)) ? $this->borderInsideVSize : null;
	}
	
	public function setCellMarginTop($pValue = null) 
        {
		$this->cellMarginTop = $pValue;
	}
	
	public function getCellMarginTop() 
        {
		return $this->cellMarginTop;
	}
	
	public function setCellMarginLeft($pValue = null) 
        {
		$this->cellMarginLeft = $pValue;
	}
	
	public function getCellMarginLeft() 
        {
		return $this->cellMarginLeft;
	}
	
	public function setCellMarginRight($pValue = null) 
        {
		$this->cellMarginRight = $pValue;
	}
	
	public function getCellMarginRight() 
        {
		return $this->cellMarginRight;
	}
	
	public function setCellMarginBottom($pValue = null) 
        {
		$this->cellMarginBottom = $pValue;
	}
	
	public function getCellMarginBottom() 
        {
		return $this->cellMarginBottom;
	}
	
	public function setCellMargin($pValue = null) 
        {
		$this->cellMarginTop = $pValue;
		$this->cellMarginLeft = $pValue;
		$this->cellMarginRight = $pValue;
		$this->cellMarginBottom = $pValue;
	}
	
	public function getCellMargin() 
        {
		return array($this->cellMarginTop, $this->cellMarginLeft, $this->cellMarginRight, $this->cellMarginBottom);
	}
}
?>