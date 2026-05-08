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
 * PHPWord_Section_MemoryImage
 *
 * @category   PHPWord
 * @package    PHPWord_Section
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Section_MemoryImage {
	
	/**
	 * Image Src
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
	 * Image Relation ID
	 * 
	 * @var string
	 */
	private $rId;
	
	/**
	 * Image Type
	 * 
	 * @var string
	 */
	private $imageType;
	
	/**
	 * Image Create function
	 * 
	 * @var string
	 */
	private $imageCreateFunc;
	
	/**
	 * Image function
	 * 
	 * @var string
	 */
	private $imageFunc;
	
	/**
	 * Image function
	 * 
	 * @var string
	 */
	private $imageExtension;
	
	
	/**
	 * Create a new Image
	 * 
	 * @param string $src
	 * @param mixed style
	 */
	public function __construct($src, $style = null) {
		$imgData = getimagesize($src);
		$this->imageType = $imgData['mime'];
		
		$_supportedImageTypes = array('image/jpeg', 'image/gif', 'image/png');
		
		if(in_array($this->imageType, $_supportedImageTypes)) {
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
			
			if($this->style->getWidth() == null && $this->style->getHeight() == null) {
				$this->style->setWidth($imgData[0]);
				$this->style->setHeight($imgData[1]);
			}
			
			$this->setFunctions();
			
			return $this;
		} else {
			return false;
		}
	}
	
	/**
	 * Set Functions
	 */
	private function setFunctions() {
		switch($this->imageType) {
			case 'image/png':
				$this->imageCreateFunc = 'imagecreatefrompng';
				$this->imageFunc = 'imagepng';
				$this->imageExtension = 'png';
				break;
			case 'image/gif':
				$this->imageCreateFunc = 'imagecreatefromgif';
				$this->imageFunc = 'imagegif';
				$this->imageExtension = 'gif';
				break;
			case 'image/jpeg': case 'image/jpg':
				$this->imageCreateFunc = 'imagecreatefromjpeg';
				$this->imageFunc = 'imagejpeg';
				$this->imageExtension = 'jpg';
				break;
		}
	}
	
	
	/**
	 * Get Image style
	 * 
	 * @return PHPWord_Style_Image
	 */
	public function getStyle() {
		return $this->style;
	}
	
	/**
	 * Get Image Relation ID
	 * 
	 * @return int
	 */
	public function getRelationId() {
		return $this->rId;
	}
	
	/**
	 * Set Image Relation ID
	 * 
	 * @param int $rId
	 */
	public function setRelationId($rId) {
		$this->rId = $rId;
	}
	
	/**
	 * Get Image Source
	 * 
	 * @return string
	 */
	public function getSource() {
		return $this->src;
	}
	
	/**
	 * Get Image Media ID
	 * 
	 * @return string
	 */
	public function getMediaId() {
		return md5($this->src);
	}
	
	/**
	 * Get Image Type
	 * 
	 * @return string
	 */
	public function getImageType() {
		return $this->imageType;
	}
	
	/**
	 * Get Image Create Function
	 * 
	 * @return string
	 */
	public function getImageCreateFunction() {
		return $this->imageCreateFunc;
	}
	
	/**
	 * Get Image Function
	 * 
	 * @return string
	 */
	public function getImageFunction() {
		return $this->imageFunc;
	}
	
	/**
	 * Get Image Extension
	 * 
	 * @return string
	 */
	public function getImageExtension() {
		return $this->imageExtension;
	}
}
?>