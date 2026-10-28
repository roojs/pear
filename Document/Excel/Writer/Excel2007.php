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

require_once 'Document/Excel/Writer/IWriter.php';

/**
 * Writes an xlsx zip. Each part is a temp file added with ZipArchive::addFile,
 * so the XML is not loaded into a PHP string.
 */
class Document_Excel_Writer_Excel2007 implements Document_Excel_Writer_IWriter
{
    var $document;
    var $writerParts = array();
    var $tempFiles = array();
    var $imageNum = 0;

    function __construct($document = null)
    {
        $this->document = $document;
        foreach (array(
            'ContentTypes',
            'Rels',
            'DocProps',
            'Workbook',
            'Styles',
            'Drawing',
        ) as $name) {
            require_once 'Document/Excel/Writer/Excel2007/' . $name . '.php';
            $className = 'Document_Excel_Writer_Excel2007_' . $name;
            $part = new $className();
            $part->parentWriter = $this;
            $this->writerParts[strtolower($name)] = $part;
        }
    }

    function save($filename)
    {
        $sheets = $this->document->sheets;
        if (!count($sheets)) {
            throw new Exception('Document_Excel has no sheets.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filename, ZIPARCHIVE::OVERWRITE) !== true) {
            if ($zip->open($filename, ZIPARCHIVE::CREATE) !== true) {
                throw new Exception('Could not open ' . $filename . ' for writing.');
            }
        }

        $sheetCount = count($sheets);
        $this->addPart($zip, '[Content_Types].xml', $this->writerParts['contenttypes']->writeContentTypes($this->document));
        $this->addPart($zip, '_rels/.rels', $this->writerParts['rels']->writeRelationships());
        $this->addPart($zip, 'docProps/app.xml', $this->writerParts['docprops']->writeDocPropsApp($this->document));
        $this->addPart($zip, 'docProps/core.xml', $this->writerParts['docprops']->writeDocPropsCore($this->document));
        $this->addPart($zip, 'xl/workbook.xml', $this->writerParts['workbook']->writeWorkbook($this->document));
        $this->addPart($zip, 'xl/_rels/workbook.xml.rels', $this->writerParts['workbook']->writeWorkbookRels($sheetCount));
        $this->addPart($zip, 'xl/styles.xml', $this->document->styles->writeStyles());
        $zip->addFile($this->themePath(), 'xl/theme/theme1.xml');

        $n = 1;
        foreach ($sheets as $sheet) {
            $this->addPart(
                $zip,
                'xl/worksheets/sheet' . $n . '.xml',
                $sheet->finish()
            );
            if (count($sheet->images)) {
                $parts = $this->writerParts['drawing']->write($sheet->images, $n, $this->imageNum);
                $this->addPart($zip, 'xl/drawings/drawing' . $n . '.xml', $parts['drawing']);
                $this->addPart($zip, 'xl/drawings/_rels/drawing' . $n . '.xml.rels', $parts['rels']);
                $this->addPart($zip, 'xl/worksheets/_rels/sheet' . $n . '.xml.rels', $parts['sheetRels']);
                foreach ($parts['media'] as $no => $path) {
                    if ($zip->addFile($path, 'xl/media/image' . $no . '.jpeg') !== true) {
                        throw new Exception('Could not add image ' . $path);
                    }
                }
            }
            $n++;
        }

        if ($zip->close() === false) {
            throw new Exception('Could not close zip file ' . $filename . '.');
        }
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }

    function addPart($zip, $name, $path)
    {
        $this->tempFiles[] = $path;
        if ($zip->addFile($path, $name) !== true) {
            throw new Exception('Could not add ' . $name . ' from ' . $path);
        }
    }

    function themePath()
    {
        $rel = 'Document/Word/_staticDocParts/theme1.xml';
        $paths = explode(PATH_SEPARATOR, get_include_path());
        foreach ($paths as $dir) {
            $path = rtrim($dir, '/\\') . '/' . $rel;
            if (is_file($path)) {
                return $path;
            }
        }
        throw new Exception('Missing ' . $rel);
    }
}
