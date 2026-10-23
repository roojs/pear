<?php
/**
 * Copyright (c) 2026 RooJ Solutions
 *
 * Based on the PHPWord writer (Copyright (c) 2011 PHPWord).
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2026 RooJ Solutions
 * @license    https://www.gnu.org/licenses/lgpl-3.0.html    LGPL-3.0
 */

/**
 * One OOXML part. Same role as Document_Word_Writer_Word2007_WriterPart.
 */
class Document_Excel_Writer_Excel2007_WriterPart
{
    var $parentWriter;
    var $partFiles = array();

    /**
     * XML is written to a temp file. finish() returns that path for ZipArchive::addFile.
     */
    function xmlWriter()
    {
        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        $xml = new XMLWriter();
        if ($xml->openUri($file) === false) {
            throw new Exception('Could not open ' . $file);
        }
        $xml->setIndent(false);
        $this->partFiles[] = array(
            'xml' => $xml,
            'file' => $file,
        );
        return $xml;
    }

    function finish($xml)
    {
        $xml->flush();
        foreach ($this->partFiles as $i => $part) {
            if (!isset($part['xml']) || $part['xml'] !== $xml) {
                continue;
            }
            $file = $part['file'];
            unset($this->partFiles[$i]);
            return $file;
        }
        throw new Exception('Unknown part file');
    }
}
