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
 * PHPWord_Style_Image
 *
 * @category   PHPWord
 * @package    PHPWord_Section
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Style_Image 
{
	
	private $width;
	private $height;
	private $align;
	
	var $_cropb;
	var $_cropl;
	var $_cropr;
	var $_cropt;

	/**
	 * Margin Top
	 * 
	 * @var int
	 */
	private $marginTop;
	
	/**
	 * Margin Left
	 * 
	 * @var int
	 */
	private $marginLeft;
	
	public function __construct() 
        {
		$this->width  = null;
		$this->height = null;
		$this->align = null;
		$this->marginTop = null;
		$this->marginLeft = null;
	}
	
	public function setStyleValue($key, $value) 
        {
		if(substr($key, 0, 1) == '_' && !property_exists($this, $key) && property_exists($this, substr($key, 1))) {
			$key = substr($key, 1);
		}
		$this->$key = $value;
	}
	
	public function getWidth() 
        {
		return $this->width;
	}
	
	public function setWidth($pValue = null) 
        {
		$this->width = $pValue;
	}
	
	public function getHeight() 
        {
		return $this->height;
	}
	
	public function setHeight($pValue = null) 
        {
		$this->height = $pValue;
	}
	
	public function getAlign() 
        {
		return $this->align;
	}
	
	public function setAlign($pValue = null) 
        {
		$this->align = $pValue;
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
	public function setMarginTop($pValue = null) 
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
	public function setMarginLeft($pValue = null) 
        {
		$this->marginLeft = $pValue;
		return $this;
	}
}
?>
