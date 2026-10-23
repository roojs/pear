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
 * Core properties follow the Word writer. App properties list worksheet names.
 */
class Document_Excel_Writer_Excel2007_DocProps extends Document_Excel_Writer_Excel2007_WriterPart
{
    function writeDocPropsApp($document)
    {
        $sheets = $document->sheets;
        $objWriter = $this->xmlWriter();
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');
        $objWriter->startElement('Properties');
        $objWriter->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/officeDocument/2006/extended-properties');
        $objWriter->writeAttribute('xmlns:vt', 'http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes');

        $objWriter->writeElement('Application', 'Microsoft Excel');
        $objWriter->writeElement('ScaleCrop', 'false');

        $objWriter->startElement('HeadingPairs');
        $objWriter->startElement('vt:vector');
        $objWriter->writeAttribute('size', '2');
        $objWriter->writeAttribute('baseType', 'variant');
        $objWriter->startElement('vt:variant');
        $objWriter->writeElement('vt:lpstr', 'Worksheets');
        $objWriter->endElement();
        $objWriter->startElement('vt:variant');
        $objWriter->writeElement('vt:i4', count($sheets));
        $objWriter->endElement();
        $objWriter->endElement();
        $objWriter->endElement();

        $objWriter->startElement('TitlesOfParts');
        $objWriter->startElement('vt:vector');
        $objWriter->writeAttribute('size', count($sheets));
        $objWriter->writeAttribute('baseType', 'lpstr');
        foreach ($sheets as $sheet) {
            $objWriter->writeElement('vt:lpstr', $sheet->name);
        }
        $objWriter->endElement();
        $objWriter->endElement();

        $objWriter->writeElement('Company', $document->properties->getCompany());
        $objWriter->writeElement('LinksUpToDate', 'false');
        $objWriter->writeElement('SharedDoc', 'false');
        $objWriter->writeElement('HyperlinksChanged', 'false');
        $objWriter->writeElement('AppVersion', '12.0000');

        $objWriter->endElement();
        return $this->finish($objWriter);
    }

    function writeDocPropsCore($document)
    {
        $properties = $document->properties;
        $objWriter = $this->xmlWriter();
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');
        $objWriter->startElement('cp:coreProperties');
        $objWriter->writeAttribute('xmlns:cp', 'http://schemas.openxmlformats.org/package/2006/metadata/core-properties');
        $objWriter->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $objWriter->writeAttribute('xmlns:dcterms', 'http://purl.org/dc/terms/');
        $objWriter->writeAttribute('xmlns:dcmitype', 'http://purl.org/dc/dcmitype/');
        $objWriter->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        $objWriter->writeElement('dc:creator', $properties->getCreator());
        $objWriter->writeElement('cp:lastModifiedBy', $properties->getLastModifiedBy());

        $objWriter->startElement('dcterms:created');
        $objWriter->writeAttribute('xsi:type', 'dcterms:W3CDTF');
        $objWriter->writeRaw(date('Y-m-d\TH:i:sP', $properties->getCreated()));
        $objWriter->endElement();

        $objWriter->startElement('dcterms:modified');
        $objWriter->writeAttribute('xsi:type', 'dcterms:W3CDTF');
        $objWriter->writeRaw(date('Y-m-d\TH:i:sP', $properties->getModified()));
        $objWriter->endElement();

        $objWriter->writeElement('dc:title', $properties->getTitle());
        $objWriter->writeElement('dc:description', $properties->getDescription());
        $objWriter->writeElement('dc:subject', $properties->getSubject());
        $objWriter->writeElement('cp:keywords', $properties->getKeywords());
        $objWriter->writeElement('cp:category', $properties->getCategory());

        $objWriter->endElement();
        return $this->finish($objWriter);
    }
}
