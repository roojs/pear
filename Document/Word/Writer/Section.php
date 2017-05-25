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
 * PHPWord_Section
 *
 * @category   PHPWord
 * @package    PHPWord_Section
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Writer_Section 
{
	
	/**
	 * Section count
	 * 
	 * @var int
	 */
	private $_sectionCount;
	
	/**
	 * Section settings
	 * 
	 * @var PHPWord_Section_Settings
	 */
	private $_settings;
	
	/**
	 * Section Element Collection
	 * 
	 * @var array
	 */
	private $_elementCollection = array();
	
	/**
	 * Section Header
	 * 
	 * @var PHPWord_Section_Header
	 */
	private $_header = null;
	
	/**
	 * Section Footer
	 * 
	 * @var PHPWord_Section_Footer
	 */
	private $_footer = null;
	
	
	/**
	 * Create a new Section
	 * 
	 * @param int $sectionCount
	 * @param mixed $settings
	 */
	public function __construct($sectionCount, $settings = null) 
        {
                require_once __DIR__ . '/Section/Settings.php';
		$this->_sectionCount = $sectionCount;
		$this->_settings = new Document_Word_Writer_Section_Settings();
		
		if(!is_null($settings) && is_array($settings)) {
			foreach($settings as $key => $value) {
				if(substr($key, 0, 1) != '_') {
					$key = '_'.$key;
				}
				$this->_settings->setSettingValue($key, $value);
			}
		}
	}
	
	/**
	 * Get Section Settings
	 * 
	 * @return PHPWord_Section_Settings
	 */
	public function getSettings() 
        {
		return $this->_settings;
	}
	
	/**
	 * Add a Text Element
	 * 
	 * @param string $text
	 * @param mixed $styleFont
	 * @param mixed $styleParagraph
	 * @return PHPWord_Section_Text
	 */
	public function addText($text, $styleFont = null, $styleParagraph = null) 
        {
                require_once __DIR__ . '/Section/Text.php';
		//$givenText = utf8_encode($text);
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
	 * @param mixed $styleFont
	 * @param mixed $styleParagraph
	 * @return PHPWord_Section_Link
	 */
	public function addLink($linkSrc, $linkName = null, $styleFont = null, $styleParagraph = null) 
        {       
                require_once __DIR__ . '/Media.php';
                require_once __DIR__ . '/Section/Link.php';
		$linkSrc = utf8_encode($linkSrc);
		if(!is_null($linkName)) {
			$linkName = utf8_encode($linkName);
		}
		
		$link = new Document_Word_Writer_Section_Link($linkSrc, $linkName, $styleFont, $styleParagraph);
		$rID = Document_Word_Writer_Media::addSectionLinkElement($linkSrc);
		$link->setRelationId($rID);
		
		$this->_elementCollection[] = $link;
		return $link;
	}
	
	/**
	 * Add a TextBreak Element
	 * 
	 * @param int $count
	 */
    
    public function addTextBreak($count = 1) {
        return self::staticAddTextBreak($count);
    }
	public static function staticAddTextBreak($th, $count = 1) 
    {
        require_once __DIR__ . '/Section/TextBreak.php';
        for($i=1; $i<=$count; $i++) {
            $th->_elementCollection[] = new Document_Word_Writer_Section_TextBreak();
        }
	}
	
    public function addPageBreak()
    {
        self::staticAddPageBreak($this);
    }
	/**
	 * Add a PageBreak Element
	 */
    
	public static function staticAddPageBreak($th) 
    {
        require_once __DIR__ . '/Section/PageBreak.php';
		$th->_elementCollection[] = new Document_Word_Writer_Section_PageBreak();
	}
	
	/**
	 * Add a Table Element
	 * 
	 * @param mixed $style
	 * @return PHPWord_Section_Table
	 */
	public function addTable($style = null) 
    {
        return self::staticAddTable($this, $style);
	}
	public static function staticAddTable($th, $style = null) 
    {
        require_once __DIR__.'/Section/Table.php';
		$table = new Document_Word_Writer_Section_Table('section', $th->_sectionCount, $style);
		$th->_elementCollection[] = $table;
		return $table;
	}
	/**
	 * Add a ListItem Element
	 * 
	 * @param string $text
	 * @param int $depth
	 * @param mixed $styleFont
     * @param mixed $styleList
	 * @param mixed $styleParagraph
	 * @return PHPWord_Section_ListItem
	 */
	public function addListItem($text, $depth = 0, $styleFont = null, $styleList = null, $styleParagraph = null) 
        {
                require_once __DIR__ . '/Section/ListItem.php';
		$text = @iconv("UTF-8", "UTF-8//IGNORE", $text);
		$listItem = new Document_Word_Writer_Section_ListItem($text, $depth, $styleFont, $styleList, $styleParagraph);
		$this->_elementCollection[] = $listItem;
		return $listItem;
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
                require_once __DIR__ . '/Section/Object.php';
                require_once __DIR__ . '/Media.php';
		$object = new Document_Word_Writer_Section_Object($src, $style);
		
		if(!is_null($object->getSource())) {
			$inf = pathinfo($src);
			$ext = $inf['extension'];
			if(strlen($ext) == 4 && strtolower(substr($ext, -1)) == 'x') {
				$ext = substr($ext, 0, -1);
			}
			
			$iconSrc = __DIR__ . '/_staticDocParts/';
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
	 * Add a Image Element
	 * 
	 * @param string $src
	 * @param mixed $style
	 * @return PHPWord_Section_Image
	 */
    public function addImage($src, $style = null) {
        return self::staticAddImage($this, $src,$style);
    }
    
	public static function staticAddImage($th, $src, $style = null) 
        {
        require_once __DIR__ . '/Section/Image.php';
        require_once __DIR__ . '/Media.php';
		$image = new Document_Word_Writer_Section_Image($src, $style);
		if(is_null($image->getSource())) {
            return false;
            trigger_error('Source does not exist or unsupported image type.');
		}
                
        $rID = Document_Word_Writer_Media::addSectionMediaElement($src, 'image');
        $image->setRelationId($rID);

        $th->_elementCollection[] = $image;
        return $image;
		 
			
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
			$rID = Document_Word_Writer_Media::addSectionMediaElement($link, 'image', $memoryImage);
			$memoryImage->setRelationId($rID);
			
			$this->_elementCollection[] = $memoryImage;
			return $memoryImage;
		} else {
			trigger_error('Unsupported image type.');
		}
	}
	
	/**
	 * Add a Table-of-Contents Element
	 * 
	 * @param mixed $styleFont
	 * @param mixed $styleTOC
	 * @return PHPWord_TOC
	 */
	public function addTOC($styleFont = null, $styleTOC = null) 
        {
                require_once __DIR__ . '/TOC.php';
		$toc = new Document_Word_Writer_TOC($styleFont, $styleTOC);
		$this->_elementCollection[] = $toc;
		return $toc;
	}
	
	/**
	 * Add a Title Element
	 * 
	 * @param string $text
	 * @param int $depth
	 * @return PHPWord_Section_Title
	 */
	public function addTitle($text, $depth = 1) 
        {
		$text = @iconv("UTF-8", "UTF-8//IGNORE", $text);
		$styles = Document_Word_Writer_Style::getStyles();
		if(array_key_exists('Heading_'.$depth, $styles)) {
			$style = 'Heading'.$depth;
		} else {
			$style = null;
		}
		require_once __DIR__ . '/Section/Title.php';
		$title = new Document_Word_Writer_Section_Title($text, $depth, $style);
		
		$data = Document_Word_Writer_TOC::addTitle($text, $depth);
		$anchor = $data[0];
		$bookmarkId = $data[1];
		
		$title->setAnchor($anchor);
		$title->setBookmarkId($bookmarkId);
		
		$this->_elementCollection[] = $title;
		return $title;
	}
	
	/**
	 * Create a new TextRun
	 * 
	 * @return PHPWord_Section_TextRun
	 */
	public function createTextRun($styleParagraph = null) 
        {
                require_once __DIR__ . '/Section/TextRun.php';
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
	 * Create a new Header
	 * 
	 * @return PHPWord_Section_Header
	 */
	public function createHeader() 
        {
                require_once __DIR__ . '/Section/Header.php';
		$header = new Document_Word_Writer_Section_Header($this->_sectionCount);
		$this->_header = $header;
		return $header;
	}
	
	/**
	 * Get Header
	 * 
	 * @return PHPWord_Section_Header
	 */
	public function getHeader() 
        {
		return $this->_header;
	}
	
	/**
	 * Create a new Footer
	 * 
	 * @return PHPWord_Section_Footer
	 */
	public function createFooter() 
        {
                require_once __DIR__ . '/Section/Footer.php';
		$footer = new Document_Word_Writer_Section_Footer($this->_sectionCount);
		$this->_footer = $footer;
		return $footer;
	}
	
	/**
	 * Get Footer
	 * 
	 * @return PHPWord_Section_Footer
	 */
	public function getFooter() 
        {
		return $this->_footer;
	}
}
?>