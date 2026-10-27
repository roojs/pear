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
 * One worksheet. Cells are appended to a temp file as each row ends.
 * finish() writes a short header, then copies that file in. The cells
 * are not kept on the sheet.
 *
 * Set column widths before finish(). Row height is the optional argument
 * to startRow(), because the row tag is already on disk after that call.
 *
 * writeNumber and writeDate take an Excel format code.
 * writeString(..., 'header') is the header look. Other strings are General.
 * A date before 1970-01-01 leaves the cell blank, including the
 * 1000-01-01 sentinel. The next write still goes in the next column.
 *
 * writeString, writeNumber, writeDate, and writeBlank accept an array.
 * Each value is written in order, with the same style.
 * writeString writes null as an empty string.
 * writeBlank() skips one column. writeBlank(n) skips n columns.
 */
class Document_Excel_Sheet
{
    var $name;
    var $columnWidths = array();
    var $cellFile = '';
    var $cellHandle = false;
    var $rowOpen = false;
    var $rowIndex = 0;
    var $colIndex = 0;
    var $maxCol = 0;
    var $lastCellRow = 0;
    var $wroteCell = false;
    var $finishedFile = '';
    var $styles = false;

    function __construct($name)
    {
        $this->name = $name;
    }

    function __destruct()
    {
        if ($this->cellHandle) {
            fclose($this->cellHandle);
            $this->cellHandle = false;
        }
        if ($this->cellFile !== '' && is_file($this->cellFile)) {
            @unlink($this->cellFile);
        }
    }

    function setColumnWidth($column, $width)
    {
        $this->columnWidths[$column] = $width;
        return $this;
    }

    /**
     * Opens the next row. $height is optional and applies to this row only.
     */
    function startRow($height = '')
    {
        $this->openCells();
        if ($this->rowOpen) {
            $this->endRow();
        }
        $this->colIndex = 0;
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startElement('row');
        $xml->writeAttribute('r', $this->rowIndex + 1);
        if ($height !== '') {
            $xml->writeAttribute('ht', $height);
            $xml->writeAttribute('customHeight', '1');
        }
        $xml->text('');
        $this->writeRaw($xml->outputMemory(false));
        $this->rowOpen = true;
        return $this;
    }

    function writeString($value, $style = '')
    {
        if (is_array($value)) {
            foreach ($value as $one) {
                $this->writeString($one, $style);
            }
            return $this;
        }
        if ($value === null) {
            $value = '';
        }
        $this->writeCell('s', $value, $style);
        return $this;
    }

    function writeNumber($value, $style = '')
    {
        if (is_array($value)) {
            foreach ($value as $one) {
                $this->writeNumber($one, $style);
            }
            return $this;
        }
        if (!is_numeric($value)) {
            throw new Exception('Document_Excel_Sheet::writeNumber() expected a number.');
        }
        $this->writeCell('n', $this->numberString($value), $style);
        return $this;
    }

    /**
     * Moves to the next column without writing a cell.
     * false skips one column. An integer skips that many.
     * An array skips one column per element.
     */
    function writeBlank($value = false)
    {
        $this->requireRow();
        if (is_array($value)) {
            foreach ($value as $one) {
                $this->colIndex++;
            }
            return $this;
        }
        $n = ($value === false) ? 1 : (int)$value;
        for ($i = 0; $i < $n; $i++) {
            $this->colIndex++;
        }
        return $this;
    }

    /**
     * $value is Y-m-d, or a string starting with Y-m-d. $style defaults to yyyy-mm-dd.
     * An empty value, or a date before 1970-01-01, leaves this column blank.
     * An array writes each date in order.
     */
    function writeDate($value, $style = 'yyyy-mm-dd')
    {
        if (is_array($value)) {
            foreach ($value as $one) {
                $this->writeDate($one, $style);
            }
            return $this;
        }
        $this->requireRow();
        if ($value === '') {
            $this->colIndex++;
            return $this;
        }
        $serial = $this->excelSerial($value);
        if ($serial < 25569) {
            $this->colIndex++;
            return $this;
        }
        $this->writeCell('n', $serial, $style);
        return $this;
    }

    function endRow()
    {
        $this->requireRow();
        $this->writeRaw('</row>');
        fflush($this->cellHandle);
        $this->rowIndex++;
        $this->rowOpen = false;
        return $this;
    }

    /**
     * Closes the cell file and returns the finished worksheet XML path.
     * The caller adds that path to the zip and deletes it afterwards.
     */
    function finish()
    {
        if ($this->finishedFile !== '') {
            return $this->finishedFile;
        }
        if ($this->rowOpen) {
            $this->endRow();
        }
        if ($this->cellHandle) {
            fflush($this->cellHandle);
            fclose($this->cellHandle);
            $this->cellHandle = false;
        }

        $outPath = tempnam(sys_get_temp_dir(), 'xlsx');
        $out = fopen($outPath, 'wb');
        if ($out === false) {
            throw new Exception('Could not open ' . $outPath);
        }
        fwrite($out, $this->headerXml());
        if ($this->cellFile !== '') {
            $this->appendFile($out, $this->cellFile);
            $cellFile = $this->cellFile;
            $this->cellFile = '';
            @unlink($cellFile);
        }
        fwrite($out, '</sheetData></worksheet>');
        fclose($out);
        $this->finishedFile = $outPath;
        return $outPath;
    }

    function columnLetter($column)
    {
        $n = $column + 1;
        $letters = '';
        while ($n > 0) {
            $n--;
            $letters = chr(65 + ($n % 26)) . $letters;
            $n = floor($n / 26);
        }
        return $letters;
    }

    /**
     * Excel 1900 date system. 1970-01-01 is serial 25569.
     */
    function excelSerial($value)
    {
        $raw = trim($value);
        if (!preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $m)) {
            throw new Exception('Document_Excel_Sheet::writeDate() expected Y-m-d, got ' . $value);
        }
        $dt = DateTime::createFromFormat('Y-m-d', $m[1], new DateTimeZone('UTC'));
        if (!$dt) {
            throw new Exception('Document_Excel_Sheet::writeDate() could not parse ' . $value);
        }
        $dt->setTime(0, 0, 0);
        return round(($dt->getTimestamp() / 86400) + 25569);
    }

    function numberString($value)
    {
        $text = rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
        if ($text === '' || $text === '-0') {
            return '0';
        }
        return $text;
    }

    function openCells()
    {
        if ($this->cellHandle) {
            return;
        }
        if ($this->finishedFile !== '') {
            throw new Exception('Document_Excel_Sheet is already finished.');
        }
        $this->cellFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $this->cellHandle = fopen($this->cellFile, 'wb');
        if ($this->cellHandle === false) {
            throw new Exception('Could not open ' . $this->cellFile);
        }
    }

    function requireRow()
    {
        if (!$this->rowOpen) {
            throw new Exception('Document_Excel_Sheet write called outside a row. Call startRow() first.');
        }
    }

    function writeCell($type, $value, $style)
    {
        $this->requireRow();
        $styleIndex = $this->styleIndex($style);
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startElement('c');
        $xml->writeAttribute('r', $this->columnLetter($this->colIndex) . ($this->rowIndex + 1));
        if ($type === 's') {
            $xml->writeAttribute('t', 'inlineStr');
        }
        if ($styleIndex > 0) {
            $xml->writeAttribute('s', $styleIndex);
        }
        if ($type === 's') {
            require_once 'Document/Word/Shared/String.php';
            $text = Document_Word_Shared_String::ControlCharacterPHP2OOXML($value);
            $xml->startElement('is');
            $xml->startElement('t');
            $xml->writeAttribute('xml:space', 'preserve');
            $xml->text($text);
            $xml->endElement();
            $xml->endElement();
        } else {
            $xml->writeElement('v', $value);
        }
        $xml->endElement();
        $this->writeRaw($xml->outputMemory(true));
        if (!$this->wroteCell || $this->colIndex > $this->maxCol) {
            $this->maxCol = $this->colIndex;
        }
        $this->lastCellRow = $this->rowIndex + 1;
        $this->wroteCell = true;
        $this->colIndex++;
    }

    function styleIndex($name)
    {
        if ($this->styles === false) {
            throw new Exception('Document_Excel_Sheet has no style table.');
        }
        return $this->styles->index($name);
    }

    function writeRaw($xml)
    {
        if (fwrite($this->cellHandle, $xml) === false) {
            throw new Exception('Could not write worksheet cells.');
        }
    }

    /**
     * Worksheet open tags, dimension, and column widths. sheetData is left open.
     * This string is only the header; the cell file is copied after it.
     */
    function headerXml()
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8', 'yes');
        $xml->startElement('worksheet');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        if ($this->wroteCell) {
            $xml->startElement('dimension');
            $xml->writeAttribute('ref', 'A1:' . $this->columnLetter($this->maxCol) . $this->lastCellRow);
            $xml->endElement();
        }
        if (count($this->columnWidths)) {
            ksort($this->columnWidths);
            $xml->startElement('cols');
            foreach ($this->columnWidths as $column => $width) {
                $n = $column + 1;
                $xml->startElement('col');
                $xml->writeAttribute('min', $n);
                $xml->writeAttribute('max', $n);
                $xml->writeAttribute('width', $width);
                $xml->writeAttribute('customWidth', '1');
                $xml->endElement();
            }
            $xml->endElement();
        }
        $xml->startElement('sheetData');
        $xml->text('');
        return $xml->outputMemory(false);
    }

    function appendFile($out, $path)
    {
        $in = fopen($path, 'rb');
        if ($in === false) {
            throw new Exception('Could not read ' . $path);
        }
        if (stream_copy_to_stream($in, $out) === false) {
            fclose($in);
            throw new Exception('Could not copy ' . $path);
        }
        fclose($in);
    }
}
