<?php
require_once 'Document/Excel/Writer/Excel2007/WriterPart.php';

class Document_Excel_Writer_Excel2007_Drawing extends Document_Excel_Writer_Excel2007_WriterPart
{
    function write($images, $sheetNo, &$nextImage)
    {
        $mediaNo = array();
        foreach ($images as $image) {
            if (isset($mediaNo[$image['path']])) {
                continue;
            }
            $nextImage++;
            $mediaNo[$image['path']] = $nextImage;
        }

        $xdr = 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing';
        $a = 'http://schemas.openxmlformats.org/drawingml/2006/main';
        $r = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

        $xml = $this->xmlWriter();
        $xml->startDocument('1.0', 'UTF-8', 'yes');
        $xml->startElement('xdr:wsDr');
        $xml->writeAttribute('xmlns:xdr', $xdr);
        $xml->writeAttribute('xmlns:a', $a);
        $xml->writeAttribute('xmlns:r', $r);

            $pic = 0;
            foreach ($images as $image) {
                $pic++;
                $xml->startElement('xdr:twoCellAnchor');
                $xml->writeAttribute('editAs', 'twoCell');

                    $this->anchorPoint($xml, 'xdr:from', $image['col'], $image['row']);
                    $this->anchorPoint($xml, 'xdr:to', $image['col'] + 1, $image['row'] + 1);

                    $xml->startElement('xdr:pic');

                        $xml->startElement('xdr:nvPicPr');

                            $xml->startElement('xdr:cNvPr');
                            $xml->writeAttribute('id', $pic + 1);
                            $xml->writeAttribute('name', 'Picture ' . $pic);
                            $xml->endElement();

                            $xml->writeRaw('
                                <xdr:cNvPicPr>
                                    <a:picLocks noChangeAspect="1"/>
                                </xdr:cNvPicPr>
                            ');

                        $xml->endElement();

                        $xml->startElement('xdr:blipFill');

                            $xml->startElement('a:blip');
                            $xml->writeAttribute('r:embed', 'rId' . $mediaNo[$image['path']]);
                            $xml->endElement();

                            $xml->writeRaw('
                                <a:stretch>
                                    <a:fillRect/>
                                </a:stretch>
                            ');

                        $xml->endElement();

                        $xml->writeRaw('
                            <xdr:spPr>
                                <a:xfrm>
                                    <a:off x="0" y="0"/>
                                    <a:ext cx="0" cy="0"/>
                                </a:xfrm>
                                <a:prstGeom prst="rect">
                                    <a:avLst/>
                                </a:prstGeom>
                            </xdr:spPr>
                        ');

                    $xml->endElement();

                    $xml->writeRaw('<xdr:clientData fLocksWithSheet="1" fPrintsWithSheet="1"/>');

                $xml->endElement();
            }

        $xml->endElement();
        $drawing = $this->finish($xml);

        $rels = $this->xmlWriter();
        $rels->startDocument('1.0', 'UTF-8', 'yes');
        $rels->startElement('Relationships');
        $rels->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');

            $media = array();
            foreach ($mediaNo as $path => $no) {
                $media[$no] = $path;
                $rels->startElement('Relationship');
                $rels->writeAttribute('Id', 'rId' . $no);
                $rels->writeAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image');
                $rels->writeAttribute('Target', '../media/image' . $no . '.jpeg');
                $rels->endElement();
            }

        $rels->endElement();

        $sheetRels = $this->xmlWriter();
        $sheetRels->startDocument('1.0', 'UTF-8', 'yes');
        $sheetRels->startElement('Relationships');
        $sheetRels->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');

            $sheetRels->startElement('Relationship');
            $sheetRels->writeAttribute('Id', 'rId1');
            $sheetRels->writeAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing');
            $sheetRels->writeAttribute('Target', '../drawings/drawing' . $sheetNo . '.xml');
            $sheetRels->endElement();

        $sheetRels->endElement();

        return array(
            'drawing' => $drawing,
            'rels' => $this->finish($rels),
            'sheetRels' => $this->finish($sheetRels),
            'media' => $media,
        );
    }

    function anchorPoint($xml, $name, $col, $row)
    {
        $col = (int) $col;
        $row = (int) $row;
        $xml->writeRaw("
            <{$name}>
                <xdr:col>{$col}</xdr:col>
                <xdr:colOff>0</xdr:colOff>
                <xdr:row>{$row}</xdr:row>
                <xdr:rowOff>0</xdr:rowOff>
            </{$name}>
        ");
    }
}
