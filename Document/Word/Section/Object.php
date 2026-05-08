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
 * PHPWord_Section_Object
 *
 * @category   PHPWord
 * @package    PHPWord_Section
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Section_Object 
{
	
	/**
	 * Ole-Object Src
	 * 
	 * @var string
	 */
	private $src;
	
	/**
	 * Image Style
	 * 
	 * @var PHPWord_Style_Image
	 */
	private $style;
	
	/**
	 * Object Relation ID
	 * 
	 * @var int
	 */
	private $rId;
	
	/**
	 * Image Relation ID
	 * 
	 * @var int
	 */
	private $rIdImg;
	
	/**
	 * Object ID
	 * 
	 * @var int
	 */
	private $objId;
	
	
	/**
	 * Create a new Ole-Object Element
	 * 
	 * @param string $src
	 * @param mixed $style
	 */
	public function __construct($src, $style = null) 
        {
                require_once __DIR__ . '/../Style/Image.php';
		$_supportedObjectTypes = array('xls', 'doc', 'ppt');
		$inf = pathinfo($src);
		
		if(file_exists($src) && in_array($inf['extension'], $_supportedObjectTypes)) {
			$this->src = $src;
			$this->style = new Document_Word_Style_Image();
			
			if(!is_null($style) && is_array($style)) {
				foreach($style as $key => $value) {
					if(substr($key, 0, 1) != '_') {
						$key = '_'.$key;
					}
					$this->style->setStyleValue($key, $value);
				}
			}
			
			return $this;
		} else {
			return false;
		}
	}
	
	/**
	 * Get Image style
	 * 
	 * @return PHPWord_Style_Image
	 */
	public function getStyle() 
        {
		return $this->style;
	}
	
	/**
	 * Get Source
	 * 
	 * @return string
	 */
	public function getSource() 
        {
		return $this->src;
	}
	
	/**
	 * Get Object Relation ID
	 * 
	 * @return int
	 */
	public function getRelationId() 
        {
		return $this->rId;
	}
	
	/**
	 * Set Object Relation ID
	 * 
	 * @param int $rId
	 */
	public function setRelationId($rId) 
        {
		$this->rId = $rId;
	}
	
	/**
	 * Get Image Relation ID
	 * 
	 * @return int
	 */
	public function getImageRelationId() 
        {
		return $this->rIdImg;
	}
	
	/**
	 * Set Image Relation ID
	 * 
	 * @param int $rId
	 */
	public function setImageRelationId($rId) 
        {
		$this->rIdImg = $rId;
	}
	
	/**
	 * Get Object ID
	 * 
	 * @return int
	 */
	public function getObjectId() 
        {
		return $this->objId;
	}
	
	/**
	 * Set Object ID
	 * 
	 * @param int $objId
	 */
	public function setObjectId($objId) 
        {
		$this->objId = $objId;
	}
}
?>
