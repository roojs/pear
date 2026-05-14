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


class Document_Word_Writer_Style_Table 
{
	
	private $_cellMarginTop;
	private $_cellMarginLeft;
	private $_cellMarginRight;
	private $_cellMarginBottom;
    public  $_fixed = false;
	
	// not used..
	var $_width0;
	var $_width0_dax;
	var $_width1;
	var $_width1_dax;
	var $_width2;
	var $_width2_dax;
	var $_width3;
	var $_width3_dax;
	var $_width4;
	var $_width4_dax;
	var $_width5;
	var $_width5_dax;
	
	var $_listtag;
	
	var $_height0;
	var $_height1;
	var $_height2;
	var $_height3;
	var $_height4;
	var $_height5;
	var $_height6;
	var $_height7;
	var $_height8;
	var $_height9;
	var $_height10;
	var $_height11;
	var $_height12;

	var $_leftstyle;
	var $_borderleftcolor;
	var $_bgstyle;
	var $_bgcolor;
	
		

	public function __construct() 
        {
		$this->_cellMarginTop = null;
		$this->_cellMarginLeft = null;
		$this->_cellMarginRight = null;
		$this->_cellMarginBottom = null;
	}
	
	public function setStyleValue($key, $value) 
    {
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
		$this->_cellMarginTop = $pValue;
	}
	
	public function getCellMarginTop() 
        {
		return $this->_cellMarginTop;
	}
	
	public function setCellMarginLeft($pValue = null) 
        {
		$this->_cellMarginLeft = $pValue;
	}
	
	public function getCellMarginLeft() 
        {
		return $this->_cellMarginLeft;
	}
	
	public function setCellMarginRight($pValue = null) 
        {
		$this->_cellMarginRight = $pValue;
	}
	
	public function getCellMarginRight() 
        {
		return $this->_cellMarginRight;
	}
	
	public function setCellMarginBottom($pValue = null) 
        {
		$this->_cellMarginBottom = $pValue;
	}
	
	public function getCellMarginBottom() 
        {
		return $this->_cellMarginBottom;
	}
	
	public function getCellMargin() 
        {
		return array($this->_cellMarginTop, $this->_cellMarginLeft, $this->_cellMarginRight, $this->_cellMarginBottom);
	}
}
?>