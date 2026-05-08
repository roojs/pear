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
 * PHPWord_Style_TOC
 *
 * @category   PHPWord
 * @package    PHPWord_Style
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Style_TOC 
{
	
	const TABLEADER_DOT         = 'dot';
	const TABLEADER_UNDERSCORE  = 'underscore';
	const TABLEADER_LINE        = 'hyphen';
	const TABLEADER_NONE        = '';
	
	/**
	 * Tab Leader
	 * 
	 * @var string
	 */
	private $tabLeader;
	
	/**
	 * Tab Position
	 * 
	 * @var int
	 */
	private $tabPos;
	
	/**
	 * Indent
	 * 
	 * @var int
	 */
	private $indent;
	
	
	/**
	 * Create a new TOC Style
	 */
	public function __construct() 
        {
		$this->tabPos      = 9062;
		$this->tabLeader   = Document_Word_Style_TOC::TABLEADER_DOT;
		$this->indent      = 200;
	}
	
	/**
	 * Get Tab Position
	 * 
	 * @return int
	 */
	public function getTabPos() 
        {
		return $this->tabPos;
	}
	
	/**
	 * Set Tab Position
	 * 
	 * @param int $pValue
	 */
	public function setTabPos($pValue) 
        {
		$this->tabLeader = $pValue;
	}
	
	/**
	 * Get Tab Leader
	 * 
	 * @return string
	 */
	public function getTabLeader() 
        {
		return $this->tabLeader;
	}
	
	/**
	 * Set Tab Leader
	 * 
	 * @param string $pValue
	 */
	public function setTabLeader($pValue = Document_Word_Style_TOC::TABLEADER_DOT) 
        {
		$this->tabLeader = $pValue;
	}
	
	/**
	 * Get Indent
	 * 
	 * @return int
	 */
	public function getIndent() 
        {
		return $this->indent;
	}
	
	/**
	 * Set Indent
	 * 
	 * @param string $pValue
	 */
	public function setIndent($pValue) 
        {
		$this->indent = $pValue;
	}
	
	/**
	 * Set style value
	 * 
	 * @param string $key
	 * @param string $value
	 */
	public function setStyleValue($key, $value) 
        {
		$this->$key = $value;
	}
}
?>
