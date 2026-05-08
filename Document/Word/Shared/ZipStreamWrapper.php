<?php
/**
 * Generic zip:// stream wrapper. Legacy load path: Document/Word/Writer/Shared/ZipStreamWrapper.php (bridge).
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


/** Register new zip wrapper */
Document_Word_Shared_ZipStreamWrapper::register();


class Document_Word_Shared_ZipStreamWrapper {
	/**
	 * Internal ZipAcrhive
	 *
	 * @var ZipAcrhive
	 */
	private $archive;

	/**
	 * Filename in ZipAcrhive
	 *
	 * @var string
	 */
	private $fileNameInArchive = '';

	/**
	 * Position in file
	 *
	 * @var int
	 */
	private $position = 0;

	/**
	 * Data
	 *
	 * @var mixed
	 */
	private $data = '';

	/**
	 * Register wrapper
	 */
	public static function register() {
		@stream_wrapper_unregister("zip");
		@stream_wrapper_register("zip", __CLASS__);
	}

	/**
	 * Open stream
	 */
	public function stream_open($path, $mode, $options, &$opened_path) {
		// Check for mode
		if ($mode[0] != 'r') {
			throw new Exception('Mode ' . $mode . ' is not supported. Only read mode is supported.');
		}

		// Parse URL
		$url = @parse_url($path);

		// Fix URL
		if (!is_array($url)) {
			$url['host'] = substr($path, strlen('zip://'));
			$url['path'] = '';
		}
		if (strpos($url['host'], '#') !== false) {
			if (!isset($url['fragment'])) {
				$url['fragment']	= substr($url['host'], strpos($url['host'], '#') + 1) . $url['path'];
				$url['host']		= substr($url['host'], 0, strpos($url['host'], '#'));
				unset($url['path']);
			}
		} else {
			$url['host']		= $url['host'] . $url['path'];
			unset($url['path']);
		}

		// Open archive
		$this->archive = new ZipArchive();
		$this->archive->open($url['host']);

		$this->fileNameInArchive = $url['fragment'];
		$this->position = 0;
		$this->data = $this->archive->getFromName( $this->fileNameInArchive );

		return true;
	}

	/**
	 * Stat stream
	 */
	public function stream_stat() {
		return $this->archive->statName( $this->fileNameInArchive );
	}

	/**
	 * Read stream
	 */
	function stream_read($count) {
		$ret = substr($this->data, $this->position, $count);
		$this->position += strlen($ret);
		return $ret;
	}

	/**
	 * Tell stream
	 */
	public function stream_tell() {
		return $this->position;
	}

	/**
	 * EOF stream
	 */
	public function stream_eof() {
		return $this->position >= strlen($this->data);
	}

	/**
	 * Seek stream
	 */
	public function stream_seek($offset, $whence) {
		switch ($whence) {
			case SEEK_SET:
				if ($offset < strlen($this->data) && $offset >= 0) {
					 $this->position = $offset;
					 return true;
				} else {
					 return false;
				}
				break;

			case SEEK_CUR:
				if ($offset >= 0) {
					 $this->position += $offset;
					 return true;
				} else {
					 return false;
				}
				break;

			case SEEK_END:
				if (strlen($this->data) + $offset >= 0) {
					 $this->position = strlen($this->data) + $offset;
					 return true;
				} else {
					 return false;
				}
				break;

			default:
				return false;
		}
	}
}
?>
