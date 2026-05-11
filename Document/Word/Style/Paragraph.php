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
 * PHPWord_Style_Paragraph
 *
 * @category   PHPWord
 * @package    PHPWord_Style
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Style_Paragraph 
{
	
	/**
	 * Paragraph alignment
	 * 
	 * @var string
	 */
	private $align;
	
	/**
	 * Space before Paragraph
	 * 
	 * @var int
	 */
	private $spaceBefore;
	
	/**
	 * Space after Paragraph
	 * 
	 * @var int
	 */
	private $spaceAfter;
	
	/**
	 * Spacing between breaks
	 * 
	 * @var int
	 */
	private $spacing;
        
    private $liststyle;
	
	// not used? but set by setter
	var $bgcolor;
	var $bold;
	var $color;
	var $fontStretch;
	var $fontVariant;
	var $italic;
	var $lineHeight;
	var $marginBottom;
	var $marginLeft;
	var $marginRight;
	var $marginTop;
	var $name;
	var $Normal;
	var $Reference;
	var $size;
	var $textDecoration;
	var $textIndent;
	var $textPosition;
	var $widows;
	var $domdir;
	
	
	/**
	 * New Paragraph Style
	 */
	public function __construct() 
        {
		$this->align           = null;
		$this->spaceBefore     = null;
		$this->spaceAfter      = null;
		$this->spacing         = null;
        $this->liststyle      = null;
	}
	
	/**
	 * Set Style value
	 * 
	 * @param string $key
	 * @param mixed $value
	 */
	public function setStyleValue($key, $value) 
        {
		if(substr($key, 0, 1) == '_' && !property_exists($this, $key) && property_exists($this, substr($key, 1))) {
			$key = substr($key, 1);
		}
		if($key == 'spacing') {
			$value += 240; // because line height of 1 matches 240 twips
		}
                
		if($key == 'list-style'){
			$key = str_replace('-', '_', $key);
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
		
		$this->$key =  $value;
		  
		 
	}

	/**
	 * Get Paragraph Alignment
	 * 
	 * @return string
	 */
	public function getAlign() 
        {
		return $this->align;
	}

	/**
	 * Set Paragraph Alignment
	 * 
	 * @param string $pValue
	 * @return PHPWord_Style_Paragraph
	 */
	public function setAlign($pValue = null) 
        {
		if(strtolower($pValue) == 'justify') {
			// justify becames both
			$pValue = 'both';
		}
		$this->align = $pValue;
		return $this;
	}

	/**
	 * Get Space before Paragraph
	 * 
	 * @return string
	 */
	public function getSpaceBefore() 
        {
		return $this->spaceBefore;
	}

	/**
	 * Set Space before Paragraph
	 * 
	 * @param int $pValue
	 * @return PHPWord_Style_Paragraph
	 */
	public function setSpaceBefore($pValue = null) 
        {
	   $this->spaceBefore = $pValue;
	   return $this;
	}

	/**
	 * Get Space after Paragraph
	 * 
	 * @return string
	 */
	public function getSpaceAfter() 
        {
		return $this->spaceAfter;
	}

	/**
	 * Set Space after Paragraph
	 * 
	 * @param int $pValue
	 * @return PHPWord_Style_Paragraph
	 */
	public function setSpaceAfter($pValue = null) 
        {
	   $this->spaceAfter = $pValue;
	   return $this;
	}

	/**
	 * Get Spacing between breaks
	 * 
	 * @return int
	 */
	public function getSpacing() 
        {
		return $this->spacing;
	}

	/**
	 * Set Spacing between breaks
	 * 
	 * @param int $pValue
	 * @return PHPWord_Style_Paragraph
	 */
	public function setSpacing($pValue = null) 
	{
	   $this->spacing = $pValue;
	   return $this;
	}
        
        /**
	 * Get Paragraph List Style
	 * 
	 * @return string
	 */
	public function getListStyle() 
        {
            return $this->liststyle;
	}
}
