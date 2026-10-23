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
 * xl/workbook.xml and xl/_rels/workbook.xml.rels.
 * rId1..n are worksheets. The next two are styles and theme.
 */
class Document_Excel_Writer_Excel2007_Workbook extends Document_Excel_Writer_Excel2007_WriterPart
{
    function writeWorkbook($document)
    {
        $objWriter = $this->xmlWriter();
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');
        $objWriter->startElement('workbook');
        $objWriter->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $objWriter->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $objWriter->startElement('sheets');
        $id = 1;
        foreach ($document->sheets as $sheet) {
            $objWriter->startElement('sheet');
            $objWriter->writeAttribute('name', $sheet->name);
            $objWriter->writeAttribute('sheetId', $id);
            $objWriter->writeAttribute('r:id', 'rId' . $id);
            $objWriter->endElement();
            $id++;
        }
        $objWriter->endElement();
        $objWriter->endElement();
        return $this->finish($objWriter);
    }

    function writeWorkbookRels($sheetCount)
    {
        $objWriter = $this->xmlWriter();
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');
        $objWriter->startElement('Relationships');
        $objWriter->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');

        for ($i = 1; $i <= $sheetCount; $i++) {
            $this->writeRelationship(
                $objWriter,
                $i,
                'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet',
                'worksheets/sheet' . $i . '.xml'
            );
        }
        $this->writeRelationship(
            $objWriter,
            $sheetCount + 1,
            'http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles',
            'styles.xml'
        );
        $this->writeRelationship(
            $objWriter,
            $sheetCount + 2,
            'http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme',
            'theme/theme1.xml'
        );

        $objWriter->endElement();
        return $this->finish($objWriter);
    }

    function writeRelationship($objWriter, $id, $type, $target)
    {
        $objWriter->startElement('Relationship');
        $objWriter->writeAttribute('Id', 'rId' . $id);
        $objWriter->writeAttribute('Type', $type);
        $objWriter->writeAttribute('Target', $target);
        $objWriter->endElement();
    }
}
