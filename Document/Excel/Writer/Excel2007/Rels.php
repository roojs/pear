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
 * Package relationships. Same three entries as the Word writer,
 * with the office document pointing at xl/workbook.xml.
 */
class Document_Excel_Writer_Excel2007_Rels extends Document_Excel_Writer_Excel2007_WriterPart
{
    function writeRelationships()
    {
        $objWriter = $this->xmlWriter();
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');
        $objWriter->startElement('Relationships');
        $objWriter->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $this->writeRelationship(
            $objWriter,
            1,
            'http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument',
            'xl/workbook.xml'
        );
        $this->writeRelationship(
            $objWriter,
            2,
            'http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties',
            'docProps/core.xml'
        );
        $this->writeRelationship(
            $objWriter,
            3,
            'http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties',
            'docProps/app.xml'
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
