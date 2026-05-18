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
 * PHPWord_Section_ListItem
 *
 * @category   PHPWord
 * @package    PHPWord_Section
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Section_ListItem 
{
	
	/**
	 * ListItem Style
	 * 
	 * @var PHPWord_Style_ListItem
	 */
	private $style;
	
	/**
	 * Textrun
	 * 
	 * @var PHPWord_Section_Text
	 */
	private $textObject;
	
	/**
	 * ListItem Depth
	 * 
	 * @var int
	 */
	private $depth;

	/**
	 * Optional rich inline elements for this list item (texts/links/textbreaks),
	 * populated by the DOCX reader when available.
	 *
	 * @var array
	 */
	private $elements = array();
	
	
	/**
	 * Create a new ListItem
	 * 
	 * @param string $text
	 * @param int $depth
	 * @param mixed $styleText
	 * @param mixed $styleList
	 */
	public function __construct($text, $depth = 0, $styleFont = null, $styleList = null, $styleParagraph = null) 
        {
                require_once __DIR__ . '/Text.php';
                require_once __DIR__ . '/../Style/ListItem.php';
		$this->style = new Document_Word_Style_ListItem();
		$this->textObject = new Document_Word_Section_Text($text, $styleFont, $styleParagraph);
		$this->depth = $depth;
		
		if(!is_null($styleList) && is_array($styleList)) {
			foreach($styleList as $key => $value) {
				if(substr($key, 0, 1) != '_') {
					$key = '_'.$key;
				}
				$this->style->setStyleValue($key, $value);
			}
		}
	}
	
	/**
	 * Get ListItem style
	 */
	public function getStyle() 
        {
		return $this->style;
	}
	
	/**
	 * Get ListItem TextRun
	 */
	public function getTextObject() 
        {
		return $this->textObject;
	}
	
	/**
	 * Get ListItem depth
	 */
	public function getDepth() 
        {
		return $this->depth;
	}

	/**
	 * Set rich inline elements for this list item.
	 *
	 * @param array $elements
	 */
	public function setElements($elements)
	{
		$this->elements = is_array($elements) ? $elements : array();
	}

	/**
	 * Get rich inline elements for this list item.
	 *
	 * @return array
	 */
	public function getElements()
	{
		return $this->elements;
	}
}
?>