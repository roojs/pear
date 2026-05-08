<?php
/**
 * Generic document metadata (creator, title, timestamps, etc.).
 *
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
 * Document metadata for {@see Document_Word}.
 *
 * @category   PHPWord
 * @package    PHPWord
 * @copyright  Copyright (c) 2009 - 2011 PHPWord (http://www.codeplex.com/PHPWord)
 */
class Document_Word_DocumentProperties 
{

	/**
	 * Creator
	 *
	 * @var string
	 */
	private $creator;
	
	/**
	 * LastModifiedBy
	 *
	 * @var string
	 */
	private $lastModifiedBy;
	
	/**
	 * Created
	 *
	 * @var datetime
	 */
	private $created;
	
	/**
	 * Modified
	 *
	 * @var datetime
	 */
	private $modified;
	
	/**
	 * Title
	 *
	 * @var string
	 */
	private $title;
	
	/**
	 * Description
	 *
	 * @var string
	 */
	private $description;
	
	/**
	 * Subject
	 *
	 * @var string
	 */
	private $subject;
	
	/**
	 * Keywords
	 *
	 * @var string
	 */
	private $keywords;
	
	/**
	 * Category
	 *
	 * @var string
	 */
	private $category;
	
	/**
	 * Company
	 * 
	 * @var string
	 */
	private $company;

	/**
	 * Create new PHPWord_DocumentProperties
	 */
	public function __construct() 
        {
		$this->creator 		= '';
		$this->lastModifiedBy  = $this->creator;
		$this->created 		= time();
		$this->modified 		= time();
		$this->title			= '';
		$this->subject			= '';
		$this->description		= '';
		$this->keywords		= '';
		$this->category		= '';
		$this->company 		= '';
	}

	/**
	 * Get Creator
	 *
	 * @return string
	 */
	public function getCreator() 
        {
		return $this->creator;
	}
	
	/**
	 * Set Creator
	 *
	 * @param string $pValue
	 * @return PHPWord_DocumentProperties
	 */
	public function setCreator($pValue = '') 
        {
		$this->creator = $pValue;
		return $this;
	}
	
	/**
	 * Get Last Modified By
	 *
	 * @return string
	 */
	public function getLastModifiedBy() 
        {
		return $this->lastModifiedBy;
	}
	
	/**
	 * Set Last Modified By
	 *
	 * @param string $pValue
	 * @return PHPWord_DocumentProperties
	 */
	public function setLastModifiedBy($pValue = '') 
        {
		$this->lastModifiedBy = $pValue;
		return $this;
	}
	
	/**
	 * Get Created
	 *
	 * @return datetime
	 */
	public function getCreated() 
        {
		return $this->created;
	}
	
	/**
	 * Set Created
	 *
	 * @param datetime $pValue
	 * @return PHPWord_DocumentProperties
	 */
	public function setCreated($pValue = null) 
        {
		if (is_null($pValue)) {
			$pValue = time();
		}
		$this->created = $pValue;
		return $this;
	}
	
	/**
	 * Get Modified
	 *
	 * @return datetime
	 */
	public function getModified() 
        {
		return $this->modified;
	}
	
	/**
	 * Set Modified
	 *
	 * @param datetime $pValue
	 * @return PHPWord_DocumentProperties
	 */
	public function setModified($pValue = null) 
        {
		if (is_null($pValue)) {
			$pValue = time();
		}
		$this->modified = $pValue;
		return $this;
	}
	
	/**
	 * Get Title
	 *
	 * @return string
	 */
	public function getTitle() 
        {
		return $this->title;
	}
	
	/**
	 * Set Title
	 *
	 * @param string $pValue
	 * @return PHPWord_DocumentProperties
	 */
	public function setTitle($pValue = '') 
        {
		$this->title = $pValue;
		return $this;
	}
	
	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function getDescription() 
        {
		return $this->description;
	}
	
	/**
	 * Set Description
	 *
	 * @param string $pValue
	 * @return PHPWord_DocumentProperties
	 */
	public function setDescription($pValue = '') 
        {
		$this->description = $pValue;
		return $this;
	}
	
	/**
	 * Get Subject
	 *
	 * @return string
	 */
	public function getSubject() 
        {
		return $this->subject;
	}
	
	/**
	 * Set Subject
	 *
	 * @param string $pValue
	 * @return PHPWord_DocumentProperties
	 */
	public function setSubject($pValue = '') 
        {
		$this->subject = $pValue;
		return $this;
	}
	
	/**
	 * Get Keywords
	 *
	 * @return string
	 */
	public function getKeywords() 
        {
		return $this->keywords;
	}
	
	/**
	 * Set Keywords
	 *
	 * @param string $pValue
	 * @return PHPWord_DocumentProperties
	 */
	public function setKeywords($pValue = '') 
        {
		$this->keywords = $pValue;
		return $this;
	}
	
	/**
	 * Get Category
	 *
	 * @return string
	 */
	public function getCategory() 
        {
		return $this->category;
	}
	
	/**
	 * Set Category
	 *
	 * @param string $pValue
	 * @return PHPWord_DocumentProperties
	 */
	public function setCategory($pValue = '') 
        {
		$this->category = $pValue;
		return $this;
	}
	
	/**
	 * Get Company
	 *
	 * @return string
	 */
	public function getCompany() 
        {
		return $this->company;
	}
	
	/**
	 * Set Company
	 *
	 * @param string $pValue
	 * @return PHPWord_DocumentProperties
	 */
	public function setCompany($pValue = '') 
        {
		$this->company = $pValue;
		return $this;
	}
}
?>
