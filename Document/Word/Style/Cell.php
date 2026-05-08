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
 * PHPWord_Style_Cell
 *
 * @category   PHPWord
 * @package    PHPWord_Style
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Style_Cell 
{
	
	const TEXT_DIR_BTLR = 'btLr';
	const TEXT_DIR_TBRL = 'tbRl';
	
	/**
	 * Vertical align
	 * 
	 * @var string
	 */
	private $valign;
	
	/**
	 * Text Direction
	 * 
	 * @var string
	 */
	private $textDirection;
	
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
	 * Border Default Color
	 * 
	 * @var string
	 */
	private $defaultBorderColor;
	
	var $_bgStyle;
	var $_botAttach;
	var $_columnNum;
	var $_mergeto;
	var $_rowNum;
	var $_leftstyle;

	/**
	 * Create a new Cell Style
	 */
	public function __construct() 
        {
		$this->valign = null;
		$this->textDirection = null;
		$this->bgColor = null;
		$this->borderTopSize = null;
		$this->borderTopColor = null;
		$this->borderLeftSize = null;
		$this->borderLeftColor = null;
		$this->borderRightSize = null;
		$this->borderRightColor = null;
		$this->borderBottomSize = null;
		$this->borderBottomColor = null;
		$this->defaultBorderColor = '000000';
	}
	
	/**
	 * Set style value
	 * 
	 * @var string $key
	 * @var mixed $value
	 */
	public function setStyleValue($key, $value) 
        {
		if($key == '_borderSize') {
			$this->setBorderSize($value);
			return;
		} 
		if ($key == '_borderColor') {
			$this->setBorderColor($value);
			return;
		}
		
		$cache = array();
		if (empty($cache)) {
			$ar = get_class_vars(get_class($this));
			foreach($ar as $k => $v) {
				$cache[strtolower($k)] = $k;
			}
		}
		$key = strtolower(str_replace('-','', $key));
		$key = isset($cache[$key]) ? $cache[$key] : $key;
		 
		$this->$key = $value;
		
	}
	
	public function getVAlign() 
        {
		return $this->valign;
	}
	
	public function setVAlign($pValue = null) 
        {
		$this->valign = $pValue;
	}
	
	public function getTextDirection() 
        {
		return $this->textDirection;
	}
	
	public function setTextDirection($pValue = null) 
        {
		$this->textDirection = $pValue;
	}
	
	public function getBgColor() 
        {
		return $this->bgColor;
	}

	public function setBgColor($pValue = null) 
        {
	   $this->bgColor = $pValue;
	}

	public function setHeight($pValue = null) 
        {
	   $this->_height = $pValue;
	}
	
	public function setBorderSize($pValue = null) 
        {
		$this->borderTopSize = $pValue;
		$this->borderLeftSize = $pValue;
		$this->borderRightSize = $pValue;
		$this->borderBottomSize = $pValue;
	}
	
	public function getBorderSize() 
        {
		$t = $this->getBorderTopSize();
		$l = $this->getBorderLeftSize();
		$r = $this->getBorderRightSize();
		$b = $this->getBorderBottomSize();
		
		return array($t, $l, $r, $b);
	}
	
	public function setBorderColor($pValue = null) 
        {
		$this->borderTopColor = $pValue;
		$this->borderLeftColor = $pValue;
		$this->borderRightColor = $pValue;
		$this->borderBottomColor = $pValue;
	}
	
	public function getBorderColor() 
        {
		$t = $this->getBorderTopColor();
		$l = $this->getBorderLeftColor();
		$r = $this->getBorderRightColor();
		$b = $this->getBorderBottomColor();
		
		return array($t, $l, $r, $b);
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
	
	public function getDefaultBorderColor() 
        {
		return $this->defaultBorderColor;
	}
}
