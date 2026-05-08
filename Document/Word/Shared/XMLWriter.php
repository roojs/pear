<?php
/**
 * Generic shared XML writer wrapper. Legacy load path: Document/Word/Writer/Shared/XMLWriter.php (bridge).
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


if(!defined('DATE_W3C')) {
	define('DATE_W3C', 'Y-m-d\TH:i:sP');
}


class Document_Word_Shared_XMLWriter 
{
	/** Temporary storage method */
	const STORAGE_MEMORY = 1;
	const STORAGE_DISK = 2;

	/**
	 * Internal XMLWriter
	 *
	 * @var XMLWriter
	 */
	private $xmlWriter;

	/**
	 * Temporary filename
	 *
	 * @var string
	 */
	private $tempFileName = '';

	/**
	 * Create a new PHPPowerPoint_Shared_XMLWriter instance
	 *
	 * @param int		$pTemporaryStorage			Temporary storage location
	 * @param string	$pTemporaryStorageFolder	Temporary storage folder
	 */
	public function __construct($pTemporaryStorage = self::STORAGE_MEMORY, $pTemporaryStorageFolder = './') 
        {
		// Create internal XMLWriter
		$this->xmlWriter = new XMLWriter();

		// Open temporary storage
		if ($pTemporaryStorage == self::STORAGE_MEMORY) {
			$this->xmlWriter->openMemory();
		} else {
			// Create temporary filename
			$this->tempFileName = @tempnam($pTemporaryStorageFolder, 'xml');

			// Open storage
			if ($this->xmlWriter->openUri($this->tempFileName) === false) {
				// Fallback to memory...
				$this->xmlWriter->openMemory();
			}
		}

		// Set default values
		// proposed to be false in production version
		$this->xmlWriter->setIndent(true);
		//$this->xmlWriter->setIndent(false);
		
		// Set indent
		// proposed to be '' in production version
		$this->xmlWriter->setIndentString('  ');
		//$this->xmlWriter->setIndentString('');
	}

	/**
	 * Destructor
	 */
	public function __destruct() 
        {
		// Desctruct XMLWriter
		unset($this->xmlWriter);

		// Unlink temporary files
		if ($this->tempFileName != '') {
			@unlink($this->tempFileName);
		}
	}

	/**
	 * Get written data
	 *
	 * @return $data
	 */
	public function getData() 
        {
		if ($this->tempFileName == '') {
			return $this->xmlWriter->outputMemory(true);
		} else {
			$this->xmlWriter->flush();
			return file_get_contents($this->tempFileName);
		}
	}

	/**
	 * Catch function calls (and pass them to internal XMLWriter)
	 *
	 * @param unknown_type $function
	 * @param unknown_type $args
	 */
	public function __call($function, $args) 
        {
		try {
			@call_user_func_array(array($this->xmlWriter, $function), $args);
		} catch (Exception $ex) {
			// Do nothing!
		}
	}

	/**
	 * Fallback method for writeRaw, introduced in PHP 5.2
	 *
	 * @param string $text
	 * @return string
	 */
	public function writeRaw($text)
	{
		if (isset($this->xmlWriter) && is_object($this->xmlWriter) && (method_exists($this->xmlWriter, 'writeRaw'))) {
			return $this->xmlWriter->writeRaw($text);
		}

		return $this->text($text);
	}
}
