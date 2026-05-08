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
 * PHPWord_Section_Settings
 *
 * @category   PHPWord
 * @package    PHPWord_Section
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Section_Settings 
{
	
	/**
	 * Default Page Size Width
	 * 
	 * @var int
	 */
	private $defaultPageSizeW = 11906;
	
	/**
	 * Default Page Size Height
	 * 
	 * @var int
	 */
	private $defaultPageSizeH = 16838;
	
	/**
	 * Page Orientation
	 * 
	 * @var string
	 */
	private $orientation;
	
	/**
	 * Page Margin Top
	 * 
	 * @var int
	 */
	private $marginTop;
	
	/**
	 * Page Margin Left
	 * 
	 * @var int
	 */
	private $marginLeft;
	
	/**
	 * Page Margin Right
	 * 
	 * @var int
	 */
	private $marginRight;
	
	/**
	 * Page Margin Bottom
	 * 
	 * @var int
	 */
	private $marginBottom;
	
	/**
	 * Page Size Width
	 * 
	 * @var int
	 */
	private $pageSizeW;
	
	/**
	 * Page Size Height
	 * 
	 * @var int
	 */
	private $pageSizeH;

	/**
	 * Page Border Top Size
	 * 
	 * @var int
	 */
	private $borderTopSize;

	/**
	 * Page Border Top Color
	 * 
	 * @var int
	 */
	private $borderTopColor;

	/**
	 * Page Border Left Size
	 * 
	 * @var int
	 */
	private $borderLeftSize;

	/**
	 * Page Border Left Color
	 * 
	 * @var int
	 */
	private $borderLeftColor;

	/**
	 * Page Border Right Size
	 * 
	 * @var int
	 */
	private $borderRightSize;

	/**
	 * Page Border Right Color
	 * 
	 * @var int
	 */
	private $borderRightColor;

	/**
	 * Page Border Bottom Size
	 * 
	 * @var int
	 */
	private $borderBottomSize;

	/**
	 * Page Border Bottom Color
	 * 
	 * @var int
	 */
	private $borderBottomColor;
	
	/**
	 * Create new Section Settings
	 */
	public function __construct() 
        {
		$this->orientation = null;
		$this->marginTop = 1418;
		$this->marginLeft = 1418;
		$this->marginRight	= 1418;
		$this->marginBottom = 1134;
		$this->pageSizeW = $this->defaultPageSizeW;
		$this->pageSizeH = $this->defaultPageSizeH;
		$this->borderTopSize = null;
		$this->borderTopColor = null;
		$this->borderLeftSize = null;
		$this->borderLeftColor = null;
		$this->borderRightSize = null;
		$this->borderRightColor = null;
		$this->borderBottomSize = null;
		$this->borderBottomColor = null;
	}
	
	/**
	 * Set Setting Value
	 * 
	 * @param string $key
	 * @param string $value
	 */
	public function setSettingValue($key, $value) 
        {
		if($key == '_orientation' && $value == 'landscape') {
			$this->setLandscape();
		} elseif($key == '_orientation' && is_null($value)) {
			$this->setPortrait();
		} elseif($key == '_borderSize') {
			$this->setBorderSize($value);
		} elseif($key == '_borderColor') {
			$this->setBorderColor($value);
		} else {
			$this->$key = $value;
		}
	}
	
	/**
	 * Get Margin Top
	 * 
	 * @return int
	 */
	public function getMarginTop() 
        {
		return $this->marginTop;
	}

	/**
	 * Set Margin Top
	 * 
	 * @param int $pValue
	 */
	public function setMarginTop($pValue = '') 
        {
		$this->marginTop = $pValue;
		return $this;
	}

	/**
	 * Get Margin Left
	 * 
	 * @return int
	 */
	public function getMarginLeft() 
        {
		return $this->marginLeft;
	}

	/**
	 * Set Margin Left
	 * 
	 * @param int $pValue
	 */
	public function setMarginLeft($pValue = '') 
        {
		$this->marginLeft = $pValue;
		return $this;
	}

	/**
	 * Get Margin Right
	 * 
	 * @return int
	 */
	public function getMarginRight() 
        {
		return $this->marginRight;
	}

	/**
	 * Set Margin Right
	 * 
	 * @param int $pValue
	 */
	public function setMarginRight($pValue = '') 
        {
		$this->marginRight = $pValue;
		return $this;
	}

	/**
	 * Get Margin Bottom
	 * 
	 * @return int
	 */
	public function getMarginBottom() 
        {
		return $this->marginBottom;
	}

	/**
	 * Set Margin Bottom
	 * 
	 * @param int $pValue
	 */
	public function setMarginBottom($pValue = '') 
        {
		$this->marginBottom = $pValue;
		return $this;
	}
	
	/**
	 * Set Landscape Orientation
	 */
	public function setLandscape() 
        {
		$this->orientation = 'landscape';
		$this->pageSizeW = $this->defaultPageSizeH;
		$this->pageSizeH = $this->defaultPageSizeW;
	}
	
	/**
	 * Set Portrait Orientation
	 */
	public function setPortrait() 
        {
		$this->orientation = null;
		$this->pageSizeW = $this->defaultPageSizeW;
		$this->pageSizeH = $this->defaultPageSizeH;
	}
	
	/**
	 * Get Page Size Width
	 * 
	 * @return int
	 */
	public function getPageSizeW() 
        {
		return $this->pageSizeW;
	}
	
	/**
	 * Get Page Size Height
	 * 
	 * @return int
	 */
	public function getPageSizeH() 
        {
		return $this->pageSizeH;
	}
	
	/**
	 * Get Page Orientation
	 * 
	 * @return string
	 */
	public function getOrientation() 
        {
		return $this->orientation;
	}
	
	/**
	 * Set Border Size
	 * 
	 * @param int $pValue
	 */
	public function setBorderSize($pValue = null) 
        {
		$this->borderTopSize = $pValue;
		$this->borderLeftSize = $pValue;
		$this->borderRightSize = $pValue;
		$this->borderBottomSize = $pValue;
	}
	
	/**
	 * Get Border Size
	 * 
	 * @return array
	 */
	public function getBorderSize() 
        {
		$t = $this->getBorderTopSize();
		$l = $this->getBorderLeftSize();
		$r = $this->getBorderRightSize();
		$b = $this->getBorderBottomSize();
		
		return array($t, $l, $r, $b);
	}
	
	/**
	 * Set Border Color
	 * 
	 * @param string $pValue
	 */
	public function setBorderColor($pValue = null) 
        {
		$this->borderTopColor = $pValue;
		$this->borderLeftColor = $pValue;
		$this->borderRightColor = $pValue;
		$this->borderBottomColor = $pValue;
	}
	
	/**
	 * Get Border Color
	 * 
	 * @return array
	 */
	public function getBorderColor() 
        {
		$t = $this->getBorderTopColor();
		$l = $this->getBorderLeftColor();
		$r = $this->getBorderRightColor();
		$b = $this->getBorderBottomColor();
		
		return array($t, $l, $r, $b);
	}
	
	/**
	 * Set Border Top Size
	 * 
	 * @param int $pValue
	 */
	public function setBorderTopSize($pValue = null) 
        {
		$this->borderTopSize = $pValue;
	}
	
	/**
	 * Get Border Top Size
	 * 
	 * @return int
	 */
	public function getBorderTopSize() 
        {
		return $this->borderTopSize;
	}
	
	/**
	 * Set Border Top Color
	 * 
	 * @param string $pValue
	 */
	public function setBorderTopColor($pValue = null) 
        {
		$this->borderTopColor = $pValue;
	}
	
	/**
	 * Get Border Top Color
	 * 
	 * @return string
	 */
	public function getBorderTopColor() 
        {
		return $this->borderTopColor;
	}
	
	/**
	 * Set Border Left Size
	 * 
	 * @param int $pValue
	 */
	public function setBorderLeftSize($pValue = null) 
        {
		$this->borderLeftSize = $pValue;
	}
	
	/**
	 * Get Border Left Size
	 * 
	 * @return int
	 */
	public function getBorderLeftSize() 
        {
		return $this->borderLeftSize;
	}
	
	/**
	 * Set Border Left Color
	 * 
	 * @param string $pValue
	 */
	public function setBorderLeftColor($pValue = null) 
        {
		$this->borderLeftColor = $pValue;
	}
	
	/**
	 * Get Border Left Color
	 * 
	 * @return string
	 */
	public function getBorderLeftColor() 
        {
		return $this->borderLeftColor;
	}
	
	/**
	 * Set Border Right Size
	 * 
	 * @param int $pValue
	 */
	public function setBorderRightSize($pValue = null) 
        {
		$this->borderRightSize = $pValue;
	}
	
	/**
	 * Get Border Right Size
	 * 
	 * @return int
	 */
	public function getBorderRightSize() 
        {
		return $this->borderRightSize;
	}
	
	/**
	 * Set Border Right Color
	 * 
	 * @param string $pValue
	 */
	public function setBorderRightColor($pValue = null) 
        {
		$this->borderRightColor = $pValue;
	}
	
	/**
	 * Get Border Right Color
	 * 
	 * @return string
	 */
	public function getBorderRightColor() 
        {
		return $this->borderRightColor;
	}
	
	/**
	 * Set Border Bottom Size
	 * 
	 * @param int $pValue
	 */
	public function setBorderBottomSize($pValue = null) 
        {
		$this->borderBottomSize = $pValue;
	}
	
	/**
	 * Get Border Bottom Size
	 * 
	 * @return int
	 */
	public function getBorderBottomSize() 
        {
		return $this->borderBottomSize;
	}
	
	/**
	 * Set Border Bottom Color
	 * 
	 * @param string $pValue
	 */
	public function setBorderBottomColor($pValue = null) 
        {
		$this->borderBottomColor = $pValue;
	}
	
	/**
	 * Get Border Bottom Color
	 * 
	 * @return string
	 */
	public function getBorderBottomColor() 
        {
		return $this->borderBottomColor;
	}
}
?>
