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


class Document_Word_Style_Table 
{
	
	private $cellMarginTop;
	private $cellMarginLeft;
	private $cellMarginRight;
	private $cellMarginBottom;
    public $fixed = false;
	
	// not used..
	var $width0;
	var $width0_dax;
	var $width1;
	var $width1_dax;
	var $width2;
	var $width2_dax;
	var $width3;
	var $width3_dax;
	var $width4;
	var $width4_dax;
	var $width5;
	var $width5_dax;
	
	var $listtag;
	
	var $height0;
	var $height1;
	var $height2;
	var $height3;
	var $height4;
	var $height5;
	var $height6;
	var $height7;
	var $height8;
	var $height9;
	var $height10;
	var $height11;
	var $height12;

	var $leftstyle;
	var $borderleftcolor;
	var $bgstyle;
	var $bgcolor;
	
		

	public function __construct() 
        {
		$this->cellMarginTop = null;
		$this->cellMarginLeft = null;
		$this->cellMarginRight = null;
		$this->cellMarginBottom = null;
	}
	
	public function setStyleValue($key, $value) 
    {
		if(substr($key, 0, 1) == '_' && !property_exists($this, $key) && property_exists($this, substr($key, 1))) {
			$key = substr($key, 1);
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
	
	public function getCellMargin() 
        {
		return array($this->cellMarginTop, $this->cellMarginLeft, $this->cellMarginRight, $this->cellMarginBottom);
	}
}
?>