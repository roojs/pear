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
 * xlsx workbook. Sheet rows are written to a temp file as they are finished.
 *
 * $book = new Document_Excel();
 * $sheet = $book->createSheet('Order Analysis');
 * $sheet->setColumnWidth(0, 18);
 * $sheet->startRow(30);
 * $sheet->writeString('Order date', 'header');
 * $sheet->endRow();
 * $book->exportAs('Excel2007', '/tmp/OrderAnalysis.xlsx');
 */
class Document_Excel
{
    var $properties;
    var $sheets = array();
    var $styles;

    function __construct()
    {
        require_once 'Document/Word/DocumentProperties.php';
        $this->properties = new Document_Word_DocumentProperties();
        require_once 'Document/Excel/Writer/Excel2007/Styles.php';
        $this->styles = new Document_Excel_Writer_Excel2007_Styles();
    }

    /**
     * Sheet tab name. Invalid characters are stripped. Max 31 characters.
     */
    function createSheet($name)
    {
        require_once 'Document/Excel/Sheet.php';
        $sheet = new Document_Excel_Sheet($this->cleanSheetName($name));
        $sheet->styles = $this->styles;
        $this->sheets[] = $sheet;
        return $sheet;
    }

    function exportAs($format, $filename)
    {
        require_once 'Document/Excel/IOFactory.php';
        $factory = new Document_Excel_IOFactory();
        $factory->createWriter($this, $format)->save($filename);
        return $this;
    }

    function cleanSheetName($name)
    {
        $name = str_replace(array('\\', '/', '*', '?', ':', '[', ']'), ' ', $name);
        $name = trim($name);
        $index = count($this->sheets) + 1;
        if ($name === '') {
            $name = 'Sheet' . $index;
        }
        if (strlen($name) > 31) {
            $name = substr($name, 0, 31);
        }
        $used = array();
        foreach ($this->sheets as $sheet) {
            $used[strtolower($sheet->name)] = true;
        }
        $base = $name;
        $n = $index;
        while (isset($used[strtolower($name)])) {
            $suffix = ' ' . $n;
            $name = substr($base, 0, 31 - strlen($suffix)) . $suffix;
            $n++;
        }
        return $name;
    }
}
