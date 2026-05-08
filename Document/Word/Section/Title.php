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
 * PHPWord_Section_Title
 *
 * @category   PHPWord
 * @package    PHPWord_Section
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Section_Title 
{
	
	/**
	 * Title Text content
	 * 
	 * @var string
	 */
	private $text;
	
	/**
	 * Title depth
	 * 
	 * @var int
	 */
	private $depth;
	
	/**
	 * Title anchor
	 * 
	 * @var int
	 */
	private $anchor;
	
	/**
	 * Title Bookmark ID
	 * 
	 * @var int
	 */
	private $bookmarkId;
	
	/**
	 * Title style
	 * 
	 * @var string
	 */
	private $style;
	
	
	/**
	 * Create a new Title Element
	 * 
	 * @var string $text
	 * @var int $depth
	 */
	public function __construct($text, $depth = 1, $style = null) 
        {
		if(!is_null($style)) {
			$this->style = $style;
		}
		
		$this->text = $text;
		$this->depth = $depth;
		
		return $this;
	}
	
	/**
	 * Set Anchor
	 * 
	 * @var int $anchor
	 */
	public function setAnchor($anchor) 
        {
		$this->anchor = $anchor;
	}
	
	/**
	 * Get Anchor
	 * 
	 * @return int
	 */
	public function getAnchor() 
        {
		return $this->anchor;
	}
	
	/**
	 * Set Bookmark ID
	 * 
	 * @var int $bookmarkId
	 */
	public function setBookmarkId($bookmarkId) 
        {
		$this->bookmarkId = $bookmarkId;
	}
	
	/**
	 * Get Anchor
	 * 
	 * @return int
	 */
	public function getBookmarkId() 
        {
		return $this->bookmarkId;
	}
	
	/**
	 * Get Title Text content
	 * 
	 * @return string
	 */
	public function getText() 
        {
		return $this->text;
	}

	/**
	 * Heading level for HTML export: 1 = largest title.
	 *
	 * @return int
	 */
	public function getDepth()
        {
		return (int) $this->depth;
	}
	
	/**
	 * Get Title style
	 * 
	 * @return string
	 */
	public function getStyle() {
		return $this->style;
	}
}
?>