<?php
/**
 * Reads a .docx (OOXML) package into {@see Document_Word} using {@see ZipArchive} and DOM.
 *
 * v1 scope: body paragraphs (runs, bold/italic/underline/size/color), line breaks, page breaks,
 * external hyperlinks, simple tables. Skips images, numbered lists, headers/footers, nested tables
 * in cells, and most w:sdt blocks (unwraps trivial sdtContent when present).
 *
 * Manual smoke from repository `pear/` directory:
 *
 * ```
 * php -r '
 * $base = sys_get_temp_dir() . "/dw-docx-smoke-" . uniqid();
 * mkdir($base, 0777, true);
 * $docx = $base . "/smoke.docx";
 * $z = new ZipArchive();
 * $z->open($docx, ZipArchive::CREATE | ZipArchive::OVERWRITE);
 * $z->addFromString("[Content_Types].xml", "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<Types xmlns=\"http://schemas.openxmlformats.org/package/2006/content-types\"><Default Extension=\"rels\" ContentType=\"application/vnd.openxmlformats-package.relationships+xml\"/><Default Extension=\"xml\" ContentType=\"application/xml\"/><Override PartName=\"/word/document.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml\"/></Types>");
 * $z->addFromString("_rels/.rels", "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\"><Relationship Id=\"rId1\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument\" Target=\"word/document.xml\"/></Relationships>");
 * $z->addFromString("word/_rels/document.xml.rels", "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\"><Relationship Id=\"rId1\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink\" Target=\"https://example.com/\" TargetMode=\"External\"/></Relationships>");
 * $z->addFromString("word/document.xml", "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<w:document xmlns:w=\"http://schemas.openxmlformats.org/wordprocessingml/2006/main\" xmlns:r=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships\"><w:body><w:p><w:r><w:t>Hello DOCX</w:t></w:r></w:p><w:p><w:hyperlink r:id=\"rId1\"><w:r><w:t>Link</w:t></w:r></w:hyperlink></w:p></w:body></w:document>");
 * $z->addFromString("docProps/core.xml", "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<cp:coreProperties xmlns:cp=\"http://schemas.openxmlformats.org/package/2006/metadata/core-properties\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\"><dc:title>SmokeTitle</dc:title><dc:creator>SmokeAuthor</dc:creator></cp:coreProperties>");
 * $z->close();
 * require_once "Document/Word.php";
 * require_once "Document/Word/IOFactory.php";
 * $w = Document_Word_IOFactory::load($docx);
 * if (strpos($w->getProperties()->getTitle(), "SmokeTitle") === false) { fwrite(STDERR, "title fail\n"); exit(1); }
 * $html = Document_Word_IOFactory::createWriter($w, "HTML")->getContent();
 * if (strpos($html, "Hello DOCX") === false || strpos($html, "example.com") === false) { fwrite(STDERR, "html fail\n"); exit(1); }
 * echo "ok\n";
 * '
 * ```
 *
 * @category Document_Word
 */
class Document_Word_Reader_Docx
{
    const NS_W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    const NS_R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    const NS_REL_PKG = 'http://schemas.openxmlformats.org/package/2006/relationships';

    const NS_DC = 'http://purl.org/dc/elements/1.1/';

    /** @var array<string, array{type:string,target:string,external:bool}> */
    private $_rels = array();

    /**
     * @param string $path Absolute or relative path to a .docx file
     * @return Document_Word
     * @throws Exception
     */
    public function load($path)
    {
        if (!is_string($path) || $path === '') {
            throw new Exception('Document_Word_Reader_Docx::load() requires a non-empty path.');
        }
        if (!is_readable($path)) {
            throw new Exception('Document_Word_Reader_Docx::load() cannot read file: ' . $path);
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new Exception('Document_Word_Reader_Docx::load() could not open OOXML package: ' . $path);
        }

        $documentXml = $zip->getFromName('word/document.xml');
        if ($documentXml === false) {
            $zip->close();
            throw new Exception('Document_Word_Reader_Docx::load() missing word/document.xml in ' . $path);
        }

        $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
        $this->_rels = $relsXml !== false ? $this->_parseRelsXml($relsXml) : array();

        $dom = new DOMDocument();
        if (@$dom->loadXML($documentXml, LIBXML_NONET) === false) {
            $zip->close();
            throw new Exception('Document_Word_Reader_Docx::load() invalid XML in word/document.xml');
        }

        require_once __DIR__ . '/../../Word.php';
        $doc = new Document_Word();
        $this->_applyCoreProperties($zip, $doc);

        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', self::NS_W);
        $xp->registerNamespace('r', self::NS_R);

        $body = $xp->query('//w:body')->item(0);
        if (!$body instanceof DOMElement) {
            $zip->close();
            throw new Exception('Document_Word_Reader_Docx::load() missing w:body');
        }

        $section = $doc->createSection();
        $this->_walkBlockContainer($section, $body, true, $xp);

        $zip->close();

        return $doc;
    }

    /**
     * @param string $xml
     * @return array<string, array{type:string,target:string,external:bool}>
     */
    private function _parseRelsXml($xml)
    {
        $out = array();
        $dom = new DOMDocument();
        if (@$dom->loadXML($xml, LIBXML_NONET) === false) {
            return $out;
        }
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('pkg', self::NS_REL_PKG);
        $nodes = $xp->query('//pkg:Relationship');
        if ($nodes === false) {
            return $out;
        }
        $prefix = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/';
        foreach ($nodes as $rel) {
            if (!$rel instanceof DOMElement) {
                continue;
            }
            $id = $rel->getAttribute('Id');
            if ($id === '') {
                continue;
            }
            $type = $rel->getAttribute('Type');
            $short = (strpos($type, $prefix) === 0) ? substr($type, strlen($prefix)) : $type;
            $out[$id] = array(
                'type' => $short,
                'target' => $rel->getAttribute('Target'),
                'external' => strcasecmp($rel->getAttribute('TargetMode'), 'External') === 0,
            );
        }

        return $out;
    }

    /**
     * @param ZipArchive $zip
     * @param Document_Word $doc
     */
    private function _applyCoreProperties($zip, $doc)
    {
        $xml = $zip->getFromName('docProps/core.xml');
        if ($xml === false) {
            return;
        }
        $dom = new DOMDocument();
        if (@$dom->loadXML($xml, LIBXML_NONET) === false) {
            return;
        }
        $title = '';
        $creator = '';
        foreach ($dom->getElementsByTagNameNS(self::NS_DC, 'title') as $n) {
            $title = trim($n->textContent);
            break;
        }
        foreach ($dom->getElementsByTagNameNS(self::NS_DC, 'creator') as $n) {
            $creator = trim($n->textContent);
            break;
        }
        if ($title !== '') {
            $doc->getProperties()->setTitle($title);
        }
        if ($creator !== '') {
            $doc->getProperties()->setCreator($creator);
        }
    }

    /**
     * @param Document_Word_Section|Document_Word_Section_Table_Cell $target
     */
    private function _walkBlockContainer($target, DOMElement $container, $isSection, DOMXPath $xp)
    {
        foreach ($container->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }
            if (!$this->_isW($child)) {
                continue;
            }
            $ln = $child->localName;
            if ($ln === 'p') {
                $this->_readParagraph($target, $child, $isSection, $xp);
                continue;
            }
            if ($ln === 'tbl') {
                if (!$isSection || !($target instanceof Document_Word_Section)) {
                    continue;
                }
                $this->_readTable($target, $child, $xp);
                continue;
            }
            if ($ln === 'sdt') {
                $content = $this->_firstChildW($child, 'sdtContent');
                if ($content instanceof DOMElement) {
                    $this->_walkBlockContainer($target, $content, $isSection, $xp);
                }
                continue;
            }
        }
    }

    /**
     * @param Document_Word_Section|Document_Word_Section_Table_Cell $target
     */
    private function _readParagraph($target, DOMElement $p, $isSection, DOMXPath $xp)
    {
        if ($isSection && $target instanceof Document_Word_Section) {
            $pPr = $this->_firstChildW($p, 'pPr');
            if ($pPr instanceof DOMElement && $this->_firstChildW($pPr, 'pageBreakBefore') !== null) {
                $target->addPageBreak();
            }
        }

        if ($this->_paragraphIsPageBreakOnly($p)) {
            if ($isSection && $target instanceof Document_Word_Section) {
                $target->addPageBreak();
            }
            return;
        }

        $plain = $this->_paragraphPlainText($p);
        if ($plain === '' && !$this->_paragraphHasRenderableContent($p)) {
            return;
        }

        $headingDepth = $this->_paragraphHeadingDepth($p);
        $hasHyperlink = $this->_paragraphHasDirectHyperlink($p);

        if ($isSection && $target instanceof Document_Word_Section && $headingDepth > 0 && !$hasHyperlink) {
            $target->addTitle($plain, $headingDepth);
            return;
        }

        if (!$isSection && $headingDepth > 0 && !$hasHyperlink) {
            $font = array('bold' => true);
            if ($headingDepth >= 2) {
                $font['size'] = max(12, 16 - $headingDepth);
            }
            $target->addText($plain, $font);
            return;
        }

        $paraStyle = $this->_paragraphStyleArray($p);
        if ($this->_paragraphIsSingleUniformText($p) && !$hasHyperlink) {
            $r = $this->_firstChildW($p, 'r');
            if ($r instanceof DOMElement) {
                $font = $this->_fontStyleFromRun($r);
                $target->addText($plain, $font === array() ? null : $font, $paraStyle === array() ? null : $paraStyle);
                return;
            }
        }

        $tr = $target->createTextRun($paraStyle === array() ? null : $paraStyle);
        $this->_emitParagraphInlines($tr, $p, $isSection && $target instanceof Document_Word_Section);
    }

    /**
     * @return bool
     */
    private function _paragraphIsPageBreakOnly(DOMElement $p)
    {
        $text = $this->_paragraphPlainText($p);
        if ($text !== '') {
            return false;
        }
        foreach ($p->getElementsByTagNameNS(self::NS_W, 'br') as $br) {
            if (!$br instanceof DOMElement) {
                continue;
            }
            if (strtolower($br->getAttributeNS(self::NS_W, 'type')) === 'page') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function _paragraphHasRenderableContent(DOMElement $p)
    {
        foreach ($p->childNodes as $c) {
            if (!$c instanceof DOMElement || !$this->_isW($c)) {
                continue;
            }
            if ($c->localName === 'r' || $c->localName === 'hyperlink') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Document_Word_Section_TextRun $tr
     * @param bool $allowPageBreak
     */
    private function _emitParagraphInlines($tr, DOMElement $p, $allowPageBreak)
    {
        foreach ($p->childNodes as $c) {
            if (!$c instanceof DOMElement || !$this->_isW($c)) {
                continue;
            }
            if ($c->localName === 'r') {
                $this->_emitRun($tr, $c, $allowPageBreak);
                continue;
            }
            if ($c->localName === 'hyperlink') {
                $this->_emitHyperlink($tr, $c);
                continue;
            }
            if ($c->localName === 'sdt') {
                $inner = $this->_firstChildW($c, 'sdtContent');
                if ($inner instanceof DOMElement) {
                    foreach ($inner->childNodes as $cc) {
                        if (!$cc instanceof DOMElement || !$this->_isW($cc)) {
                            continue;
                        }
                        if ($cc->localName === 'r') {
                            $this->_emitRun($tr, $cc, $allowPageBreak);
                        }
                        if ($cc->localName === 'hyperlink') {
                            $this->_emitHyperlink($tr, $cc);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Document_Word_Section_TextRun $tr
     * @param bool $allowPageBreak
     */
    private function _emitRun($tr, DOMElement $r, $allowPageBreak)
    {
        $fontBase = $this->_fontStyleFromRun($r);
        foreach ($r->childNodes as $c) {
            if (!$c instanceof DOMElement || !$this->_isW($c)) {
                continue;
            }
            if ($c->localName === 't') {
                $text = $this->_textFromT($c);
                if ($text !== '') {
                    $tr->addText($text, $fontBase === array() ? null : $fontBase);
                }
                continue;
            }
            if ($c->localName === 'br') {
                $type = strtolower($c->getAttributeNS(self::NS_W, 'type'));
                if ($type === 'page') {
                    if ($allowPageBreak) {
                        $tr->addPageBreak();
                    }
                    continue;
                }
                $tr->addTextBreak(1);
                continue;
            }
            if ($c->localName === 'tab') {
                $tr->addText("\t", $fontBase === array() ? null : $fontBase);
            }
        }
    }

    /**
     * @param Document_Word_Section_TextRun $tr
     */
    private function _emitHyperlink($tr, DOMElement $h)
    {
        $rid = $h->getAttributeNS(self::NS_R, 'id');
        $href = $this->_hyperlinkHref($rid);
        if ($href === null) {
            return;
        }
        $label = $this->_hyperlinkPlainText($h);
        $font = null;
        foreach ($h->getElementsByTagNameNS(self::NS_W, 'r') as $r) {
            if ($r instanceof DOMElement) {
                $f = $this->_fontStyleFromRun($r);
                if ($f !== array()) {
                    $font = $f;
                }
                break;
            }
        }
        $tr->addLink($href, $label !== '' ? $label : $href, $font);
    }

    /**
     * @return string|null
     */
    private function _hyperlinkHref($rid)
    {
        if ($rid === '' || !isset($this->_rels[$rid])) {
            return null;
        }
        $rel = $this->_rels[$rid];
        if ($rel['type'] !== 'hyperlink') {
            return null;
        }
        if ($rel['external']) {
            return $rel['target'];
        }

        return null;
    }

    /**
     * @param Document_Word_Section $section
     */
    private function _readTable($section, DOMElement $tbl, DOMXPath $xp)
    {
        $table = $section->addTable();
        foreach ($tbl->childNodes as $rowEl) {
            if (!$rowEl instanceof DOMElement || !$this->_isW($rowEl) || $rowEl->localName !== 'tr') {
                continue;
            }
            $table->addRow();
            foreach ($rowEl->childNodes as $cellEl) {
                if (!$cellEl instanceof DOMElement || !$this->_isW($cellEl) || $cellEl->localName !== 'tc') {
                    continue;
                }
                $width = $this->_cellWidthTwips($cellEl);
                $cell = $table->addCell($width > 0 ? (int) max(1, round($width / 20)) : 0);
                foreach ($cellEl->childNodes as $inner) {
                    if (!$inner instanceof DOMElement || !$this->_isW($inner)) {
                        continue;
                    }
                    if ($inner->localName === 'p') {
                        $this->_readParagraph($cell, $inner, false, $xp);
                    }
                }
            }
        }
    }

    /**
     * @return int
     */
    private function _cellWidthTwips(DOMElement $tc)
    {
        $tcPr = $this->_firstChildW($tc, 'tcPr');
        if (!$tcPr instanceof DOMElement) {
            return 0;
        }
        $tcW = $this->_firstChildW($tcPr, 'tcW');
        if (!$tcW instanceof DOMElement) {
            return 0;
        }
        $w = $tcW->getAttributeNS(self::NS_W, 'w');
        if ($w === '' || !ctype_digit($w)) {
            return 0;
        }

        return (int) $w;
    }

    /**
     * @return bool
     */
    private function _paragraphHasDirectHyperlink(DOMElement $p)
    {
        foreach ($p->childNodes as $c) {
            if ($c instanceof DOMElement && $this->_isW($c) && $c->localName === 'hyperlink') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function _paragraphIsSingleUniformText(DOMElement $p)
    {
        $rCount = 0;
        foreach ($p->childNodes as $c) {
            if (!$c instanceof DOMElement || !$this->_isW($c)) {
                continue;
            }
            if ($c->localName === 'hyperlink' || $c->localName === 'sdt') {
                return false;
            }
            if ($c->localName === 'r') {
                $rCount++;
                if ($rCount > 1) {
                    return false;
                }
                if (!$this->_runIsPlainTextOnly($c)) {
                    return false;
                }
            }
        }

        return $rCount === 1;
    }

    /**
     * @return bool
     */
    private function _runIsPlainTextOnly(DOMElement $r)
    {
        foreach ($r->childNodes as $c) {
            if (!$c instanceof DOMElement || !$this->_isW($c)) {
                continue;
            }
            if ($c->localName !== 't' && $c->localName !== 'rPr') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return int 0 if not a heading
     */
    private function _paragraphHeadingDepth(DOMElement $p)
    {
        $pPr = $this->_firstChildW($p, 'pPr');
        if (!$pPr instanceof DOMElement) {
            return 0;
        }
        $outline = $this->_firstChildW($pPr, 'outlineLvl');
        if ($outline instanceof DOMElement) {
            $v = $outline->getAttributeNS(self::NS_W, 'val');
            if ($v !== '' && ctype_digit($v)) {
                return min(6, max(1, (int) $v + 1));
            }
        }
        $pStyle = $this->_firstChildW($pPr, 'pStyle');
        if (!$pStyle instanceof DOMElement) {
            return 0;
        }
        $val = $pStyle->getAttributeNS(self::NS_W, 'val');
        if ($val === '') {
            return 0;
        }
        if (preg_match('/heading\s*(\d+)/i', $val, $m)) {
            return min(6, max(1, (int) $m[1]));
        }
        if (preg_match('/^Heading\s*(\d+)$/i', $val, $m)) {
            return min(6, max(1, (int) $m[1]));
        }
        if (strcasecmp($val, 'Title') === 0) {
            return 1;
        }

        return 0;
    }

    /**
     * @return array
     */
    private function _paragraphStyleArray(DOMElement $p)
    {
        $pPr = $this->_firstChildW($p, 'pPr');
        if (!$pPr instanceof DOMElement) {
            return array();
        }
        $jc = $this->_firstChildW($pPr, 'jc');
        if (!$jc instanceof DOMElement) {
            return array();
        }
        $val = $jc->getAttributeNS(self::NS_W, 'val');
        if ($val === '') {
            return array();
        }
        $map = array(
            'center' => 'center',
            'right' => 'right',
            'left' => 'left',
            'both' => 'both',
            'justify' => 'both',
        );
        $k = strtolower($val);
        if (!isset($map[$k])) {
            return array();
        }

        return array('align' => $map[$k]);
    }

    /**
     * @return array
     */
    private function _fontStyleFromRun(DOMElement $r)
    {
        require_once __DIR__ . '/../Style/Font.php';
        $rPr = $this->_firstChildW($r, 'rPr');
        if (!$rPr instanceof DOMElement) {
            return array();
        }
        $style = array();
        if ($this->_firstChildW($rPr, 'b') !== null || $this->_firstChildW($rPr, 'bCs') !== null) {
            $style['bold'] = true;
        }
        if ($this->_firstChildW($rPr, 'i') !== null || $this->_firstChildW($rPr, 'iCs') !== null) {
            $style['italic'] = true;
        }
        $u = $this->_firstChildW($rPr, 'u');
        if ($u instanceof DOMElement) {
            $uv = $u->getAttributeNS(self::NS_W, 'val');
            $style['underline'] = $this->_mapUnderline($uv);
        }
        $sz = $this->_firstChildW($rPr, 'sz');
        if ($sz instanceof DOMElement) {
            $half = $sz->getAttributeNS(self::NS_W, 'val');
            if ($half !== '' && ctype_digit($half)) {
                $style['size'] = max(1, (int) round(((int) $half) / 2));
            }
        }
        $color = $this->_firstChildW($rPr, 'color');
        if ($color instanceof DOMElement) {
            $cv = $color->getAttributeNS(self::NS_W, 'val');
            if ($cv !== '' && strcasecmp($cv, 'auto') !== 0) {
                if (strlen($cv) === 8) {
                    $cv = substr($cv, 2);
                }
                $style['color'] = $cv;
            }
        }
        $fonts = $this->_firstChildW($rPr, 'rFonts');
        if ($fonts instanceof DOMElement) {
            $name = $fonts->getAttributeNS(self::NS_W, 'ascii');
            if ($name === '') {
                $name = $fonts->getAttributeNS(self::NS_W, 'hAnsi');
            }
            if ($name !== '') {
                $style['name'] = $name;
            }
        }

        return $style;
    }

    /**
     * @param string|null $val
     * @return string
     */
    private function _mapUnderline($val)
    {
        require_once __DIR__ . '/../Style/Font.php';
        if ($val === null || $val === '') {
            return Document_Word_Style_Font::UNDERLINE_SINGLE;
        }
        $v = strtolower($val);
        if ($v === 'none' || $v === 'false') {
            return Document_Word_Style_Font::UNDERLINE_NONE;
        }
        static $map = null;
        if ($map === null) {
            $map = array(
                'single' => Document_Word_Style_Font::UNDERLINE_SINGLE,
                'words' => Document_Word_Style_Font::UNDERLINE_WORDS,
                'double' => Document_Word_Style_Font::UNDERLINE_DOUBLE,
                'thick' => Document_Word_Style_Font::UNDERLINE_HEAVY,
                'dotted' => Document_Word_Style_Font::UNDERLINE_DOTTED,
                'dash' => Document_Word_Style_Font::UNDERLINE_DASH,
                'dotDash' => Document_Word_Style_Font::UNDERLINE_DOTHASH,
                'dotDotDash' => Document_Word_Style_Font::UNDERLINE_DOTDOTDASH,
                'wave' => Document_Word_Style_Font::UNDERLINE_WAVY,
            );
        }
        if (isset($map[$v])) {
            return $map[$v];
        }

        return Document_Word_Style_Font::UNDERLINE_SINGLE;
    }

    /**
     * @return string
     */
    private function _paragraphPlainText(DOMElement $p)
    {
        $xp = new DOMXPath($p->ownerDocument);
        $xp->registerNamespace('w', self::NS_W);
        $nodes = $xp->query('.//w:t', $p);
        if ($nodes === false) {
            return '';
        }
        $parts = array();
        foreach ($nodes as $t) {
            if ($t instanceof DOMElement) {
                $parts[] = $this->_textFromT($t);
            }
        }

        return implode('', $parts);
    }

    /**
     * @return string
     */
    private function _hyperlinkPlainText(DOMElement $h)
    {
        $xp = new DOMXPath($h->ownerDocument);
        $xp->registerNamespace('w', self::NS_W);
        $nodes = $xp->query('.//w:t', $h);
        if ($nodes === false) {
            return '';
        }
        $parts = array();
        foreach ($nodes as $t) {
            if ($t instanceof DOMElement) {
                $parts[] = $this->_textFromT($t);
            }
        }

        return implode('', $parts);
    }

    /**
     * @return string
     */
    private function _textFromT(DOMElement $t)
    {
        return $t->textContent;
    }

    /**
     * @return bool
     */
    private function _isW(DOMElement $el)
    {
        return $el->namespaceURI === self::NS_W;
    }

    /**
     * @param string $local
     * @return DOMElement|null
     */
    private function _firstChildW(DOMElement $parent, $local)
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof DOMElement && $this->_isW($c) && $c->localName === $local) {
                return $c;
            }
        }

        return null;
    }
}
