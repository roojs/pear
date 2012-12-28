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
 * PHPWord_Section_Table_Cell
 *
 * @category   PHPWord
 * @package    PHPWord_Section_Table
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Writer_Section_Table_Cell 
{
	
	/**
	 * Cell Width
	 * 
	 * @var int
	 */
	private $_width = null;
	
	/**
	 * Cell Style
	 * 
	 * @var PHPWord_Style_Cell
	 */
	private $_style;
	
	/**
	 * Cell Element Collection
	 * 
	 * @var array
	 */
	private $_elementCollection = array();
	
	/**
	 * Table holder
	 * 
	 * @var string
	 */
	private $_insideOf;
	
	/**
	 * Section/Header/Footer count
	 * 
	 * @var int
	 */
	private $_pCount;
	
	
	/**
	 * Create a new Table Cell
	 * 
	 * @param string $insideOf
	 * @param int $pCount
	 * @param int $width
	 * @param mixed $style
	 */
	public function __construct($insideOf, $pCount, $width = null, $style = null) 
        {
                require_once __DIR__ . '/../../Style/Cell.php';
		$this->_insideOf = $insideOf;
		$this->_pCount = $pCount;
		$this->_width = $width;
		
		if(!is_null($style)) {
			if(is_array($style)) {
				$this->_style = new Document_Word_Writer_Style_Cell();
				
				foreach($style as $key => $value) {
					if(substr($key, 0, 1) != '_') {
						$key = '_'.$key;
					}
					$this->_style->setStyleValue($key, $value);
				}
			} else {
				$this->_style = $style;
			}
		}
	}
	
	/**
	 * Add a Text Element
	 * 
	 * @param string $text
	 * @param mixed $style
	 * @return PHPWord_Section_Text
	 */
	public function addText($text, $styleFont = null, $styleParagraph = null) 
        {
                require_once __DIR__ . '/../Text.php';
		//$text = utf8_encode($text);
                $text = @iconv("UTF-8", "UTF-8//IGNORE", $text);
		$text = new Document_Word_Writer_Section_Text($text, $styleFont, $styleParagraph);
		$this->_elementCollection[] = $text;
		return $text;
	}
	
	/**
	 * Add a Link Element
	 * 
	 * @param string $linkSrc
	 * @param string $linkName
	 * @param mixed $style
	 * @return PHPWord_Section_Link
	 */
	public function addLink($linkSrc, $linkName = null, $style = null) 
        {
                require_once __DIR__ . '/../../Media.php';
                require_once __DIR__ . '/../Link.php';
		if($this->_insideOf == 'section') {
			$linkSrc = utf8_encode($linkSrc);
			if(!is_null($linkName)) {
				$linkName = utf8_encode($linkName);
			}
			
			$link = new Document_Word_Writer_Section_Link($linkSrc, $linkName, $style);
			$rID = Document_Word_Writer_Media::addSectionLinkElement($linkSrc);
			$link->setRelationId($rID);
			
			$this->_elementCollection[] = $link;
			return $link;
		} else {
			trigger_error('Unsupported Link header / footer reference');
			return false;
		}
	}
	
	/**
	 * Add a TextBreak Element
	 * 
	 * @param int $count
	 */
	public function addTextBreak() 
        {
		$this->_elementCollection[] = new Document_Word_Writer_Section_TextBreak();
	}
	
	/**
	 * Add a ListItem Element
	 * 
	 * @param string $text
	 * @param int $depth
	 * @param mixed $styleText
	 * @param mixed $styleList
	 * @return PHPWord_Section_ListItem
	 */
	public function addListItem($text, $depth = 0, $styleText = null, $styleList = null) 
        {
		$text = iconv(mb_detect_encoding($text), "UTF-8", $text);
		$listItem = new Document_Word_Writer_Section_ListItem($text, $depth, $styleText, $styleList);
		$this->_elementCollection[] = $listItem;
		return $listItem;
	}
	
	/**
	 * Add a Image Element
	 * 
	 * @param string $src
	 * @param mixed $style
	 * @return PHPWord_Section_Image
	 */
	public function addImage($src, $style = null) 
        {
                require_once __DIR__ . '/../Image.php';
		$image = new Document_Word_Writer_Section_Image($src, $style);
		
		if(!is_null($image->getSource())) {
                        require_once __DIR__ . '/../../Media.php';
			if($this->_insideOf == 'section') {
				$rID = Document_Word_Writer_Media::addSectionMediaElement($src, 'image');
			} elseif($this->_insideOf == 'header') {
				$rID = Document_Word_Writer_Media::addHeaderMediaElement($this->_pCount, $src);
			} elseif($this->_insideOf == 'footer') {
				$rID = Document_Word_Writer_Media::addFooterMediaElement($this->_pCount, $src);
			}
			$image->setRelationId($rID);
			
			$this->_elementCollection[] = $image;
			return $image;
		} else {
			trigger_error('Source does not exist or unsupported image type.');
		}
	}
	
	/**
	 * Add a by PHP created Image Element
	 * 
	 * @param string $link
	 * @param mixed $style
	 * @return PHPWord_Section_MemoryImage
	 */
	public function addMemoryImage($link, $style = null) 
        {
		$memoryImage = new Document_Word_Writer_Section_MemoryImage($link, $style);
		if(!is_null($memoryImage->getSource())) {
			if($this->_insideOf == 'section') {
				$rID = Document_Word_Writer_Media::addSectionMediaElement($link, 'image', $memoryImage);
			} elseif($this->_insideOf == 'header') {
				$rID = Document_Word_Writer_Media::addHeaderMediaElement($this->_pCount, $link, $memoryImage);
			} elseif($this->_insideOf == 'footer') {
				$rID = Document_Word_Writer_Media::addFooterMediaElement($this->_pCount, $link, $memoryImage);
			}
			$memoryImage->setRelationId($rID);
			
			$this->_elementCollection[] = $memoryImage;
			return $memoryImage;
		} else {
			trigger_error('Unsupported image type.');
		}
	}
	
	/**
	 * Add a OLE-Object Element
	 * 
	 * @param string $src
	 * @param mixed $style
	 * @return PHPWord_Section_Object
	 */
	public function addObject($src, $style = null) 
        {
		$object = new Document_Word_Writer_Section_Object($src, $style);
		
		if(!is_null($object->getSource())) {
			$inf = pathinfo($src);
			$ext = $inf['extension'];
			if(strlen($ext) == 4 && strtolower(substr($ext, -1)) == 'x') {
				$ext = substr($ext, 0, -1);
			}
			
			$iconSrc = Document_Word_Writer_BASE_PATH . 'PHPWord/_staticDocParts/';
			if(!file_exists($iconSrc.'_'.$ext.'.png')) {
				$iconSrc = $iconSrc.'_default.png';
			} else {
				$iconSrc .= '_'.$ext.'.png';
			}
			
			$rIDimg = Document_Word_Writer_Media::addSectionMediaElement($iconSrc, 'image');
			$data = Document_Word_Writer_Media::addSectionMediaElement($src, 'oleObject');
			$rID = $data[0];
			$objectId = $data[1];
			
			$object->setRelationId($rID);
			$object->setObjectId($objectId);
			$object->setImageRelationId($rIDimg);
			
			$this->_elementCollection[] = $object;
			return $object;
		} else {
			trigger_error('Source does not exist or unsupported object type.');
		}
	}
	
	/**
	 * Add a PreserveText Element
	 * 
	 * @param string $text
	 * @param mixed $styleFont
	 * @param mixed $styleParagraph
	 * @return PHPWord_Section_Footer_PreserveText
	 */
	public function addPreserveText($text, $styleFont = null, $styleParagraph = null) 
        {
                require_once __DIR__ . '/../Footer/PreserveText.php';
		if($this->_insideOf == 'footer' || $this->_insideOf == 'header') {
			$text = iconv(mb_detect_encoding($text), "UTF-8", $text);
			$ptext = new Document_Word_Writer_Section_Footer_PreserveText($text, $styleFont, $styleParagraph);
			$this->_elementCollection[] = $ptext;
			return $ptext;
		} else {
			trigger_error('addPreserveText only supported in footer/header.');
		}
	}
	
	/**
	 * Create a new TextRun
	 * 
	 * @return PHPWord_Section_TextRun
	 */
	public function createTextRun($styleParagraph = null) 
        {
                require_once __DIR__ . '/../TextRun.php';
		$textRun = new Document_Word_Writer_Section_TextRun($styleParagraph);
		$this->_elementCollection[] = $textRun;
		return $textRun;
	}
	
	/**
	 * Get all Elements
	 * 
	 * @return array
	 */
	public function getElements() 
        {
		return $this->_elementCollection;
	}
	
	/**
	 * Get Cell Style
	 * 
	 * @return PHPWord_Style_Cell
	 */
	public function getStyle() 
        {
		return $this->_style;
	}
	
	/**
	 * Get Cell width
	 * 
	 * @return int
	 */
	public function getWidth() 
        {
		return $this->_width;
	}
}
