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
 * PHPWord_Section_TextRun
 *
 * @category   PHPWord
 * @package    PHPWord_Section
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Writer_Section_TextRun 
{
	
	/**
	 * Paragraph style
	 * 
	 * @var PHPWord_Style_Font
	 */
	private $_styleParagraph;
	
	/**
	 * Text collection
	 * 
	 * @var array
	 */
	public $_elementCollection;
	
	
	/**
	 * Create a new TextRun Element
	 */
	public function __construct($styleParagraph = null) 
        {
                require_once __DIR__ . '/../Style/Paragraph.php';
		$this->_elementCollection = array();
		
		// Set paragraph style
		if(is_array($styleParagraph)) {
			$this->_styleParagraph = new Document_Word_Writer_Style_Paragraph();
			
			foreach($styleParagraph as $key => $value) {
				if(substr($key, 0, 1) != '_') {
					$key = '_'.$key;
				}
				$this->_styleParagraph->setStyleValue($key, $value);
			}
		} else {
			$this->_styleParagraph = $styleParagraph;
		}
	}
	
	
	/**
	 * Add a Text Element
	 * 
	 * @var string $text
	 * @var mixed $styleFont
	 * @return PHPWord_Section_Text
	 */
	public function addText($text = null, $styleFont = null) 
        {
                require_once __DIR__ . '/Text.php';
		//$text = utf8_encode($text);
                $text = @iconv("UTF-8", "UTF-8//IGNORE", $text);
		$text = new Document_Word_Writer_Section_Text($text, $styleFont);
		$this->_elementCollection[] = $text;
		return $text;
	}
	
	/**
	 * Add a Link Element
	 * 
	 * @param string $linkSrc
	 * @param string $linkName
	 * @param mixed $styleFont
	 * @return PHPWord_Section_Link
	 */
	public function addLink($linkSrc, $linkName = null, $styleFont = null) 
        {
                require_once __DIR__ . '/Link.php';
                require_once __DIR__ . '/../Media.php';
//                $text = @iconv("UTF-8", "UTF-8//IGNORE", $text);
//		$linkSrc = utf8_encode($linkSrc);
		if(!is_null($linkName)) {
                    $linkName = @iconv("UTF-8", "UTF-8//IGNORE", $linkName);
//			$linkName = utf8_encode($linkName);
		}
		
		$link = new Document_Word_Writer_Section_Link($linkSrc, $linkName, $styleFont);
		$rID = Document_Word_Writer_Media::addSectionLinkElement($linkSrc);
		$link->setRelationId($rID);
		
		$this->_elementCollection[] = $link;
		return $link;
	}
	public function addImage($src, $style = null) 
    {
        Document_Word_Writer_Section::staticAddImage($this,$src, $style);
    }
        
    public function addTextBreak($count = 1) 
    {
        Document_Word_Writer_Section::staticAddTextBreak($this,$count);
    }
    public function addPageBreak() 
    {      
        Document_Word_Writer_Section::staticAddPageBreak($this);
	}
        
    public function addPreserveText($text, $styleFont = null, $styleParagraph = null) 
    {
        require_once __DIR__ . '/Footer.php';
        Document_Word_Writer_Section_Footer::addPreserveText($text, $styleFont, $styleParagraph);
	}
        
	/**
	 * Get TextRun content
	 * 
	 * @return string
	 */
	public function getElements() 
        {
		return $this->_elementCollection;
	}
	
	/**
	 * Get Paragraph style
	 * 
	 * @return PHPWord_Style_Paragraph
	 */
	public function getParagraphStyle() 
        {
		return $this->_styleParagraph;
	}
    
}
