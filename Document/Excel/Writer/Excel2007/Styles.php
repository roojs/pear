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

require_once 'Document/Excel/Writer/Excel2007/WriterPart.php';

/**
 * Fixed stylesheet. Cell style indexes stay in this order:
 *   0 general
 *   1 header  (bold, wrap, grey fill)
 *   2 date    (yyyy-mm-dd)
 *   3 money   (0.000)
 *   4 int     (0)
 */
class Document_Excel_Writer_Excel2007_Styles extends Document_Excel_Writer_Excel2007_WriterPart
{
    function index($name)
    {
        $map = array(
            '' => 0,
            'general' => 0,
            'header' => 1,
            'date' => 2,
            'money' => 3,
            'int' => 4,
        );
        if (!isset($map[$name])) {
            throw new Exception('Unknown Document_Excel style: ' . $name);
        }
        return $map[$name];
    }

    function writeStyles()
    {
        $objWriter = $this->xmlWriter();
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');
        $objWriter->startElement('styleSheet');
        $objWriter->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $objWriter->startElement('numFmts');
        $objWriter->writeAttribute('count', '2');
        $this->numFmt($objWriter, '164', 'yyyy-mm-dd');
        $this->numFmt($objWriter, '165', '0.000');
        $objWriter->endElement();

        $objWriter->startElement('fonts');
        $objWriter->writeAttribute('count', '2');
        $this->font($objWriter, false);
        $this->font($objWriter, true);
        $objWriter->endElement();

        $objWriter->startElement('fills');
        $objWriter->writeAttribute('count', '3');
        $this->fill($objWriter, 'none', '');
        $this->fill($objWriter, 'gray125', '');
        $this->fill($objWriter, 'solid', 'FFD9D9D9');
        $objWriter->endElement();

        $objWriter->startElement('borders');
        $objWriter->writeAttribute('count', '1');
        $objWriter->startElement('border');
        foreach (array('left', 'right', 'top', 'bottom', 'diagonal') as $edge) {
            $objWriter->writeElement($edge, '');
        }
        $objWriter->endElement();
        $objWriter->endElement();

        $objWriter->startElement('cellStyleXfs');
        $objWriter->writeAttribute('count', '1');
        $this->xf($objWriter, '0', '0', '0', false);
        $objWriter->endElement();

        $objWriter->startElement('cellXfs');
        $objWriter->writeAttribute('count', '5');
        $this->xf($objWriter, '0', '0', '0', false);
        $this->xf($objWriter, '0', '1', '2', true);
        $this->xf($objWriter, '164', '0', '0', false);
        $this->xf($objWriter, '165', '0', '0', false);
        $this->xf($objWriter, '1', '0', '0', false);
        $objWriter->endElement();

        $objWriter->startElement('cellStyles');
        $objWriter->writeAttribute('count', '1');
        $objWriter->startElement('cellStyle');
        $objWriter->writeAttribute('name', 'Normal');
        $objWriter->writeAttribute('xfId', '0');
        $objWriter->writeAttribute('builtinId', '0');
        $objWriter->endElement();
        $objWriter->endElement();

        $objWriter->endElement();
        return $this->finish($objWriter);
    }

    function numFmt($objWriter, $id, $code)
    {
        $objWriter->startElement('numFmt');
        $objWriter->writeAttribute('numFmtId', $id);
        $objWriter->writeAttribute('formatCode', $code);
        $objWriter->endElement();
    }

    function font($objWriter, $bold)
    {
        $objWriter->startElement('font');
        if ($bold) {
            $objWriter->writeElement('b', '');
        }
        $objWriter->startElement('sz');
        $objWriter->writeAttribute('val', '11');
        $objWriter->endElement();
        $objWriter->startElement('name');
        $objWriter->writeAttribute('val', 'Calibri');
        $objWriter->endElement();
        $objWriter->endElement();
    }

    function fill($objWriter, $pattern, $rgb)
    {
        $objWriter->startElement('fill');
        $objWriter->startElement('patternFill');
        $objWriter->writeAttribute('patternType', $pattern);
        if ($rgb !== '') {
            $objWriter->startElement('fgColor');
            $objWriter->writeAttribute('rgb', $rgb);
            $objWriter->endElement();
            $objWriter->startElement('bgColor');
            $objWriter->writeAttribute('indexed', '64');
            $objWriter->endElement();
        }
        $objWriter->endElement();
        $objWriter->endElement();
    }

    function xf($objWriter, $numFmtId, $fontId, $fillId, $header)
    {
        $objWriter->startElement('xf');
        $objWriter->writeAttribute('numFmtId', $numFmtId);
        $objWriter->writeAttribute('fontId', $fontId);
        $objWriter->writeAttribute('fillId', $fillId);
        $objWriter->writeAttribute('borderId', '0');
        $objWriter->writeAttribute('xfId', '0');
        if ($numFmtId !== '0') {
            $objWriter->writeAttribute('applyNumberFormat', '1');
        }
        if ($header) {
            $objWriter->writeAttribute('applyFont', '1');
            $objWriter->writeAttribute('applyFill', '1');
            $objWriter->writeAttribute('applyAlignment', '1');
            $objWriter->startElement('alignment');
            $objWriter->writeAttribute('wrapText', '1');
            $objWriter->writeAttribute('vertical', 'center');
            $objWriter->endElement();
        }
        $objWriter->endElement();
    }
}
