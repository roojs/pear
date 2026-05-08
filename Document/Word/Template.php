<?php
/**
 * Generic .docx template (placeholder replace) for {@see Document_Word}.
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
 * Load a .docx template and replace ${placeholder} values.
 *
 * @category   PHPWord
 * @package    PHPWord
 * @copyright  Copyright (c) 2009 - 2011 PHPWord (http://www.codeplex.com/PHPWord)
 */
class Document_Word_Template 
{
    
    /**
     * ZipArchive
     * 
     * @var ZipArchive
     */
    private $objZip;
    
    /**
     * Temporary Filename
     * 
     * @var string
     */
    private $tempFileName;
    
    /**
     * Document XML
     * 
     * @var string
     */
    private $documentXML;
    
    
    /**
     * Create a new Template Object
     * 
     * @param string $strFilename
     */
    public function __construct($strFilename) 
    {
        $path = dirname($strFilename);
        $this->tempFileName = $path.DIRECTORY_SEPARATOR.time().'.docx';
        
        copy($strFilename, $this->tempFileName); // Copy the source File to the temp File

        $this->objZip = new ZipArchive();
        $this->objZip->open($this->tempFileName);
        
        $this->documentXML = $this->objZip->getFromName('word/document.xml');
    }
    
    /**
     * Set a Template value
     * 
     * @param mixed $search
     * @param mixed $replace
     */
    public function setValue($search, $replace) 
    {
        if(substr($search, 0, 2) !== '${' && substr($search, -1) !== '}') {
            $search = '${'.$search.'}';
        }
        
        if(!is_array($replace)) {
            $replace = utf8_encode($replace);
        }
        
        $this->documentXML = str_replace($search, $replace, $this->documentXML);
    }
    
    /**
     * Save Template
     * 
     * @param string $strFilename
     */
    public function save($strFilename) 
    {
        if(file_exists($strFilename)) {
            unlink($strFilename);
        }
        
        $this->objZip->addFromString('word/document.xml', $this->documentXML);
        
        // Close zip file
        if($this->objZip->close() === false) {
            throw new Exception('Could not close zip file.');
        }
        
        rename($this->tempFileName, $strFilename);
    }
}
?>
