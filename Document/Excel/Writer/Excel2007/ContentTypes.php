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

class Document_Excel_Writer_Excel2007_ContentTypes extends Document_Excel_Writer_Excel2007_WriterPart
{
    function writeContentTypes($document)
    {
        $sheetCount = count($document->sheets);
        $objWriter = $this->xmlWriter();
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');
        $objWriter->startElement('Types');
        $objWriter->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/content-types');

        $this->writeDefault($objWriter, 'rels', 'application/vnd.openxmlformats-package.relationships+xml');
        $this->writeDefault($objWriter, 'xml', 'application/xml');

        $this->writeOverride(
            $objWriter,
            '/xl/workbook.xml',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml'
        );
        for ($i = 1; $i <= $sheetCount; $i++) {
            $this->writeOverride(
                $objWriter,
                '/xl/worksheets/sheet' . $i . '.xml',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml'
            );
        }
        $this->writeOverride(
            $objWriter,
            '/xl/styles.xml',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml'
        );
        $this->writeOverride(
            $objWriter,
            '/xl/theme/theme1.xml',
            'application/vnd.openxmlformats-officedocument.theme+xml'
        );
        $this->writeOverride(
            $objWriter,
            '/docProps/core.xml',
            'application/vnd.openxmlformats-package.core-properties+xml'
        );
        $this->writeOverride(
            $objWriter,
            '/docProps/app.xml',
            'application/vnd.openxmlformats-officedocument.extended-properties+xml'
        );

        $n = 1;
        $wroteJpeg = false;
        foreach ($document->sheets as $sheet) {
            if (count($sheet->images)) {
                if (!$wroteJpeg) {
                    $this->writeDefault($objWriter, 'jpeg', 'image/jpeg');
                    $wroteJpeg = true;
                }
                $this->writeOverride(
                    $objWriter,
                    '/xl/drawings/drawing' . $n . '.xml',
                    'application/vnd.openxmlformats-officedocument.drawing+xml'
                );
            }
            $n++;
        }

        $objWriter->endElement();
        return $this->finish($objWriter);
    }

    function writeDefault($objWriter, $extension, $contentType)
    {
        $objWriter->startElement('Default');
        $objWriter->writeAttribute('Extension', $extension);
        $objWriter->writeAttribute('ContentType', $contentType);
        $objWriter->endElement();
    }

    function writeOverride($objWriter, $partName, $contentType)
    {
        $objWriter->startElement('Override');
        $objWriter->writeAttribute('PartName', $partName);
        $objWriter->writeAttribute('ContentType', $contentType);
        $objWriter->endElement();
    }
}
