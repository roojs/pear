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
 * PHPWord_Section_Table
 *
 * @category   PHPWord
 * @package    PHPWord_Section
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Section_Table 
{
	
	/**
	 * Table style
	 *
	 * @var PHPWord_Style_Table
	 */
	private $style;
	
	/**
	 * Table rows
	 *
	 * @var array
	 */
	private $rows = array();
	
	/**
	 * Row heights
	 *
	 * @var array
	 */
	private $rowHeights = array();
        /**
	 * Row heights
	 *
	 * @var array
	 */
	private $colWidths = array();
        
	/**
	 * Table holder
	 *
	 * @var string
	 */
	private $insideOf = null;
	
	/**
	 * Table holder count
	 *
	 * @var array
	 */
	private $pCount;
	
	
	/**
	 * Create a new table
	 * 
	 * @param string $insideOf
	 * @param int $pCount
	 * @param mixed $style
	 */
	public function __construct($insideOf, $pCount, $style = null) 
        {
            $this->insideOf = $insideOf;
            $this->pCount = $pCount;
            
            if (is_null($style)) {
                return;
            }
            if (!is_array($style)) {
                $this->style = $style;
                return;
            }
            
            require_once __DIR__ . '/../Style/Table.php';
            $this->style = new Document_Word_Style_Table();
            
            foreach ($style as $key => $value) {
                if (substr($key, 0, 1) != '_') {
                    $key = '_' . $key;
                }
                $this->style->setStyleValue($key, $value);
            }

	}
	
	/**
	* Add a row
	*
	* @param int $height
	*/
	public function addRow($height = null) 
        {
		$this->rows[] = array();
		$this->rowHeights[] = $height;
	}
	
	/**
	* Add a cell
	*
	* @param int $width
	* @param mixed $style
	* @return PHPWord_Section_Table_Cell
	*/
	public function addCell($width, $style = null) 
        {
            $width = (int) $width;
            require_once __DIR__.'/Table/Cell.php';
            $cell = new Document_Word_Section_Table_Cell($this->insideOf, $this->pCount, $width, $style);
            $i = count($this->rows) - 1;
            $this->rows[$i][] = $cell;
            $col = count($this->rows[$i] ) -1;

            if (!empty($width)) {
                $this->colWidths[$col] = max($width, empty($this->colWidths[$col] ) ? 0 : $this->colWidths[$col] ); 
            }
            return $cell;
	}
	
	/**
	 * Get all rows
	 * 
	 * @return array
	 */
	public function getRows() 
        {
		return $this->rows;
	}
	
	/**
	 * Get all row heights
	 * 
	 * @return array
	 */
	public function getRowHeights() 
        {
		return $this->rowHeights;
	}
	function getColumnWidths()
        {
            return $this->colWidths;
        }
	/**
	 * Get table style
	 * 
	 * @return PHPWord_Style_Table
	 */
	public function getStyle() 
        {
		return $this->style;
	}
}
 